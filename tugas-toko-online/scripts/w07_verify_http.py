"""Real HTTP/CSRF evidence using the existing Laravel app and an isolated SQLite DB.
Usage: python scripts/w07_verify_http.py --php C:/xampp/php/php.exe --work-dir ABS_PATH
No existing database, .env, or login credentials are modified. Secrets are not exported.
"""
import argparse
import base64
import datetime
import http.cookiejar
import json
import os
from pathlib import Path
import re
import secrets
import socket
import sqlite3
import subprocess
import time
import urllib.error
import urllib.parse
import urllib.request

parser = argparse.ArgumentParser(description=__doc__)
parser.add_argument('--php', default='php')
parser.add_argument('--work-dir', required=True, type=Path)
parser.add_argument('--output-dir', type=Path)
args = parser.parse_args()
root = Path(__file__).resolve().parents[1]
if (root / 'bootstrap/cache/config.php').is_file():
    raise RuntimeError('Clear the application config cache before dummy verification; no database was modified.')
work = args.work_dir.resolve() / ('w07-http-' + datetime.datetime.now().strftime('%Y%m%d-%H%M%S'))
work.mkdir(parents=True, exist_ok=False)
out = (args.output_dir or root / 'docs/w07/evidence').resolve()
out.mkdir(parents=True, exist_ok=True)
db = work / 'dummy.sqlite'
db.touch()
password = secrets.token_urlsafe(24)
env = os.environ.copy()
env.update(APP_ENV='local', APP_DEBUG='false', DB_CONNECTION='sqlite', DB_DATABASE=os.path.relpath(db, root / 'database').replace(chr(92), '/'),
           DB_URL='', SESSION_DRIVER='database', CACHE_STORE='array', LOG_CHANNEL='stderr',
           ROLE_DEMO_PASSWORD=password)

def run_php(code):
    boot = "require 'vendor/autoload.php'; $app = require 'bootstrap/app.php'; $app->make(Illuminate\\Contracts\\Console\\Kernel::class)->bootstrap(); "
    p = subprocess.run([args.php, '-r', boot + code], cwd=root, env=env, capture_output=True, text=True)
    if p.returncode:
        raise RuntimeError('PHP helper failed: ' + p.stderr)
    return p.stdout.strip()

resolved = json.loads(run_php("echo json_encode(['driver'=>config('database.default'), 'database'=>realpath(config('database.connections.sqlite.database'))]);"))
assert resolved['driver'] == 'sqlite' and Path(resolved['database']).resolve() == db.resolve(), 'Dummy DB isolation failed; no migrations executed'
p = subprocess.run([args.php, 'artisan', 'migrate', '--no-interaction', '--no-ansi'], cwd=root, env=env, capture_output=True, text=True)
assert p.returncode == 0, p.stderr
run_php("(new Database\\Seeders\\RoleDemoSeeder)->run(); App\\Models\\Product::create(['name'=>'HTTP Buku','price'=>5000,'stock'=>10]);")
con = sqlite3.connect(db)
users = {name: ident for ident, name in con.execute('select id,username from users')}
admin_id = users['w07_admin']; target_id = users['w07_customer']; staff_id = users['w07_staff']
role_url = f'/admin/users/{target_id}/role'
with socket.socket() as sock:
    sock.bind(('127.0.0.1', 0)); port = sock.getsockname()[1]
base = f'http://127.0.0.1:{port}'
server_log = work / 'server.log'
log_handle = server_log.open('w', encoding='utf-8')
server = subprocess.Popen([args.php, '-S', f'127.0.0.1:{port}', str(root / 'vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php')], cwd=root / 'public', env=env, stdout=log_handle, stderr=log_handle)

class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, req, fp, code, msg, headers, newurl):
        return None

class Client:
    def __init__(self):
        self.jar = http.cookiejar.CookieJar()
        self.opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(self.jar), NoRedirect)
        self.token = None

    def request(self, method, path, payload=None, use_json=True, csrf=True):
        headers = {'Accept': 'application/json' if use_json else 'text/html'}
        if self.token and csrf:
            headers['X-CSRF-TOKEN'] = self.token
        data = None
        if payload is not None:
            headers['Content-Type'] = 'application/json' if use_json else 'application/x-www-form-urlencoded'
            data = (json.dumps(payload) if use_json else urllib.parse.urlencode(payload)).encode()
        req = urllib.request.Request(base + path, data=data, method=method, headers=headers)
        try:
            response = self.opener.open(req, timeout=15)
        except urllib.error.HTTPError as e:
            response = e
        body = response.read().decode('utf-8')
        try:
            response_body = json.loads(body)
        except ValueError:
            # HTML bodies contain session tokens: export only a redacted summary.
            response_body = 'HTML response (body omitted to protect CSRF/session data)'
        return {'method': method, 'url': base + path, 'status': response.status,
                'request': payload, 'response': response_body,
                'location': response.headers.get('Location')}, body

    def login(self, username):
        response, body = self.request('GET', '/login', use_json=False)
        assert response['status'] == 200
        self.token = re.search(r'name="_token" value="([^"]+)"', body).group(1)
        response, _ = self.request('POST', '/login', {'username': username, 'password': password}, use_json=False)
        assert response['status'] == 302, response['status']
        response['request']['password'] = '[REDACTED]'
        # Login regenerates the session; refresh the CSRF token from an authenticated page.
        catalog, body = self.request('GET', '/', use_json=False)
        assert catalog['status'] == 200
        self.token = re.search(r'name="_token" value="([^"]+)"', body).group(1)
        return response

def snapshot():
    return {'users': [dict(zip(['id','username','role','updated_at'], row)) for row in con.execute('select id,username,role,updated_at from users order by id')],
            'products': [dict(zip(['id','price','stock'],row)) for row in con.execute('select id,price,stock from products order by id')],
            'orders': con.execute('select count(*) from orders').fetchone()[0]}

def events():
    return [line.strip() for line in server_log.read_text(encoding='utf-8', errors='replace').splitlines() if 'user.role.changed' in line]

records = []
def check(label, prediction, action, status, changed=False, logs=0):
    before = snapshot(); log_before = len(events())
    response, _ = action()
    after = snapshot(); new_events = events()[log_before:]
    assert response['status'] == status, (label, response)
    assert len(new_events) == logs, (label, new_events)
    assert (before != after) == changed, (label, before, after)
    records.append({'id': label, 'prediction_before_execution': prediction, 'http': response,
                    'database_before': before, 'database_after': after,
                    'observer_count_before': log_before, 'observer_count_after': log_before + len(new_events),
                    'observer_delta': len(new_events), 'observer_lines': new_events, 'result': 'LOLOS PENGUJIAN'})
    print(label, status, 'DB changed:', changed, 'Observer delta:', len(new_events), flush=True)

try:
    guest = Client()
    for _ in range(100):
        try:
            if guest.request('GET','/login',use_json=False)[0]['status'] == 200:
                break
        except (OSError, urllib.error.URLError):
            time.sleep(0.2)
    else:
        raise RuntimeError('Local HTTP server did not start')
    guest_page, guest_body = guest.request('GET', '/login', use_json=False)
    guest.token = re.search(r'name="_token" value="([^"]+)"', guest_body).group(1)
    customer = Client(); admin = Client(); staff = Client()
    for name, client in [('w07_customer',customer),('w07_admin',admin),('w07_staff',staff)]:
        login = client.login(name)
        records.append({'id': 'LOGIN-'+name, 'prediction_before_execution': '302 /dashboard; session login tersimpan', 'http': login, 'result': 'LOLOS PENGUJIAN'})
    check('GUEST', '302 ke login; DB/log tetap', lambda: guest.request('POST','/cart/add',{},use_json=False),302)
    check('CSRF', '419 tanpa token; DB/log tetap', lambda: admin.request('PATCH',role_url,{'role':'staff'},csrf=False),419)
    check('CART-W06','200 total 10000 dari payload cart; DB/log tetap',lambda:customer.request('POST','/cart/add',{'cart':{'1':1},'product_id':1}),200)
    assert records[-1]['http']['response']['total'] == 10000
    check('AC-01','302; customer -> staff; satu log',lambda:admin.request('PATCH',role_url,{'role':'staff'}),302,True,1)
    assert con.execute('select role from users where id=?',(target_id,)).fetchone()[0] == 'staff'
    check('SAME-SESSION-REVOKED','403 pada sesi customer yang sudah login setelah berubah staff',lambda:customer.request('POST','/cart/add',{'cart':{'1':1},'product_id':1}),403)
    check('AC-03','422; admin sendiri ditolak service; DB/log tetap',lambda:admin.request('PATCH',f'/admin/users/{admin_id}/role',{'role':'customer'}),422)
    check('AC-04','422; role admin tidak valid; DB/log tetap',lambda:admin.request('PATCH',role_url,{'role':'admin'}),422)
    check('AC-05','302; role staff sama; DB/log tetap',lambda:admin.request('PATCH',role_url,{'role':'staff'}),302)
    check('AC-06','403 staff; DB/log tetap; payload invalid membuktikan validasi cart tidak dicapai',lambda:staff.request('POST','/cart/add',{'cart':'invalid'}),403)
    check('RESTORE-CUSTOMER','302; staff -> customer; satu log',lambda:admin.request('PATCH',role_url,{'role':'customer'}),302,True,1)
    check('AC-02','403 customer; DB/log tetap',lambda:customer.request('PATCH',role_url,{'role':'staff'}),403)
    check('CART-STATELESS','200 tanpa cart lama pada payload kosong',lambda:customer.request('POST','/cart/add',{'cart':{},'product_id':1}),200)
    assert records[-1]['http']['response']['total'] == 5000
    check('LOGOUT-W05','302 login; DB/log tetap',lambda:customer.request('POST','/logout',{},use_json=False),302)
    check('DASHBOARD-AFTER-LOGOUT','302 login; sesi tidak terautentikasi',lambda:customer.request('GET','/dashboard',use_json=False),302)
    # Observer experiment through CLI on the SAME dummy DB; null actor is expected.
    cli_log = work/'cli-observer.log'
    experiment = []
    for label, code, expected in [
        ('instance-save', f"$u=App\\Models\\User::findOrFail({target_id}); $u->role='staff'; $u->save();", 1),
        ('bulk-update', f"App\\Models\\User::whereIn('id', [{target_id}, {staff_id}])->update(['role'=>'customer']);", 0),
    ]:
        before=snapshot()
        boot="require 'vendor/autoload.php'; $app=require 'bootstrap/app.php'; $app->make(Illuminate\\Contracts\\Console\\Kernel::class)->bootstrap(); "
        p=subprocess.run([args.php,'-r',boot+code],cwd=root,env=env,capture_output=True,text=True)
        assert p.returncode == 0, p.stderr
        lines=[line.strip() for line in p.stderr.splitlines() if 'user.role.changed' in line]
        assert len(lines)==expected
        with cli_log.open('a',encoding='utf-8') as f: f.write(p.stderr)
        after=snapshot(); assert before!=after
        experiment.append({'operation':label,'prediction_before_execution':f'DB berubah, {expected} log Observer',
                           'database_before':before,'database_after':after,'observer_delta':len(lines),'observer_lines':lines})
    assert con.execute('select role from users where id=?',(target_id,)).fetchone()[0] == 'customer'
    run_php(f"App\\Models\\User::whereKey({staff_id})->update(['role'=>'staff']);")
    # Sessions store authentication, not cart payload.
    assert not any(b'cart' in base64.b64decode(row[0]).lower() for row in con.execute('select payload from sessions'))
    data={'executed_at':datetime.datetime.now(datetime.timezone(datetime.timedelta(hours=7))).isoformat(),
          'environment':'existing Laravel application; real loopback HTTP; isolated SQLite dummy; database sessions; CSRF enabled',
          'records':records,'observer_experiment':experiment,'role_restored':'customer','final_dummy_database':snapshot(),
          'server_observer_event_count':len(events()),'session_cart_stored':False}
    (out/'http-db-log.json').write_text(json.dumps(data,ensure_ascii=False,indent=2),encoding='utf-8')
    (out/'observer-events.txt').write_text('\n'.join(events()+[line for e in experiment for line in e['observer_lines']])+'\n',encoding='utf-8')
    print('HTTP, database, session and Observer experiments PASSED. Evidence:',out)
finally:
    con.close()
    # Stop only the process started by this runner; user services are untouched.
    server.terminate()
    try: server.wait(timeout=10)
    except subprocess.TimeoutExpired: server.kill(); server.wait()
    log_handle.close()
