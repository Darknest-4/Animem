# Animem – kódátvilágítás és fejlesztési terv

Készült: 2026-09-10 · Vizsgált állapot: `main` (`cf99a08`)
Terjedelem: 1948 fájl, ~92 MB, 111 PHP + 206 PHTML fájl, ~37 adatbázis tábla

---

## 0. Összefoglaló

Az oldal egy ~10 éves, fokozatosan ránőtt PHP kódbázis. Négy különböző generáció él egymás mellett benne (`html/*.php` scriptek → `html/Views` → `html/NewViews` → `private/` MVC-kísérlet), és egyik sem lett befejezve. A kód működik, de:

- **Az admin felület jelenleg gyakorlatilag védtelen**, a felhasználói bejelentkezés pedig egyetlen, kliens által szabadon átírható sütire épül. Ez nem "fejleszthetőség", hanem azonnal javítandó biztonsági rés.
- **Nincs semmilyen fejlesztői infrastruktúra**: nincs Docker, nincs SQL séma, nincs Composer, nincs teszt, nincs CI. Egy új fejlesztő nem tudja elindítani a projektet.
- **A produkciós adatbázis-jelszó három helyen is be van commitolva** a repóba.

A terv három nagy szakaszban javasolja a rendezést: **P0 – vérzéscsillapítás (1-2 hét)**, **P1 – alap infrastruktúra (2-4 hét)**, **P2 – fokozatos újraírás (folyamatos)**.

| Terület | Jelenlegi állapot | Cél |
|---|---|---|
| Biztonság | Kritikus rések | Prepared statements, valódi session, CSRF, jelszó hash |
| Auth | Sütis "userID" | Session-alapú login + regisztráció + jelszó-visszaállítás |
| Jogosultság | 3 párhuzamos, félkész rendszer | Egy RBAC réteg, minden végponton kikényszerítve |
| Feature flag | Nincs (kommentelt kód, IP-hardcode) | Adatbázis + config alapú flag rendszer |
| Indítás | Kézi LAMP beállítás | `docker compose up` – egy parancs |
| Séma | Nincs verziózva | `schema.sql` + migrációk + seed |
| Struktúra | 4 generáció keveredik | `src/` PSR-4, egy belépési pont |

---

## 1. P0 – Kritikus biztonsági hibák

Ezek a pontok éles rendszert érintenek. Javításuk nem várhat a refaktorra.

### 1.1 Az admin felület védelme ki van kapcsolva

`html/admin/.htaccess` – a teljes `AuthType Basic` blokk **ki van kommentelve** (7-42. sor, azaz a fájl végéig). Ugyanakkor a `html/uploaders/.htaccess` élő auth-blokkja olyan útvonalra mutat (`/var/www/html/animem.org/html/htpasswd/...`), ami a repóban nem létezik – ha a fájl hiányzik, az Apache 500-at ad vagy (rossz konfig mellett) átengedi a kérést.

**Hatás:** `/admin/*` alatti szerkesztő-felületek hitelesítés nélkül elérhetők lehetnek.
**Teendő:** azonnali ellenőrzés éles szerveren; ideiglenesen visszakapcsolni a Basic Autht, hosszabb távon alkalmazásszintű auth (5. fejezet).

### 1.2 A htpasswd fájlok a webgyökér alatt vannak és be vannak commitolva

`html/admin/htpasswd/` – 6 fájl, benne `{SHA}` (sózatlan SHA-1) és `$apr1$` (MD5-crypt) hashek, valós felhasználónevekkel (`admin`, `igor`, `viking`, `rex711`, `Gacsi`, `animem-admin`).

Mindkét hash-algoritmus offline töréshez ma már triviálisan gyenge. A fájlok ráadásul a dokumentumgyökér alatt vannak, tehát ha az Apache nem tiltja külön, egyszerűen letölthetők: `https://.../admin/htpasswd/onlyAdmin.htpasswd`.

**Teendő:** fájlok törlése a repóból és a webgyökérből, **minden érintett jelszó cseréje**, git history tisztítása (`git filter-repo`).

### 1.3 A bejelentkezés egyetlen, hamisítható sütire épül

`html/index.php:129` és `html/other/index-header.php:43-58`:

```php
setcookie("userID", $id, time() + (86400 * 365), "/");   // login
// ...
if (!isset($_COOKIE["userID"])) return FALSE;             // logged()
$id = SelectNew("SELECT `id` FROM `users` WHERE `id` = {$_COOKIE["userID"]} LIMIT 1;");
```

A `userID` süti **nincs aláírva, nincs titkosítva, nem session-höz kötött**. Aki a böngészőjében `userID=1`-re állítja, az az 1-es azonosítójú felhasználó (jellemzően az admin) lesz. A süti 1 év élettartamú, `HttpOnly`/`Secure`/`SameSite` flag nélkül.

Ugyanez az érték megy be szűretlenül SQL-be – tehát ez egyszerre **auth bypass és SQL injection** ugyanazon a ponton.

**Teendő:** teljes csere PHP session alapú authra (5. fejezet). Ez a legmagasabb prioritású pont az egész listában.

### 1.4 Jogosultság-ellenőrzés ugyanazon a hamisítható sütin

`html/OtherCode/allInMyFunctions.php:102-108`:

```php
function perm($code, $type = NULL)
{
  // ...
  if (isset($_COOKIE["userID"])) {
  $pg_ids = Select("SELECT `perm_groups_id` FROM `perm__user` WHERE `user_id` = {$_COOKIE["userID"]} LIMIT 1;");
```

A `perm()` függvény a kliens által megadott ID-ra kérdez rá. Ráadásul a `$type` paraméter is bekerül a tábla- és oszlopnevekbe (`perm__{$type}`), és a függvény **nem ad vissza értéket minden ágon** (implicit `NULL`), ami hívási helytől függően "engedélyezett"-ként is értelmeződhet.

### 1.5 SQL injection – rendszerszintű, nem elszigetelt

A kódbázisban **egyetlen prepared statement sincs**. Minden lekérdezés stringösszefűzéssel készül:

- `html/OtherCode/databaseFunction.php` – a `SelectNew()`, `InsertNew()`, `UpdateNew()`, `DeleteNew()` mind kész SQL stringet kap.
- `html/api.php:36` – `WHERE l.uploaders_id = {$id}` (itt van `is_numeric` szűrés, de a minta végigvonul a kódon).
- `html/api.php:63` – `WHERE inda_link LIKE '%" . $username . "%'` – itt a `$username` **külső HTTP válaszból** jön (indavideo AMF API), tehát a szűrés nemcsak hiányzik, hanem a forrás sem megbízható.
- `private/Model/FanSub.php:28` – `WHERE U.id = {$id}`
- `html/other/index-header.php:150-170` – statisztika-írás `$_SESSION` és IP alapján.

A `getip()` (`html/OtherCode/allInMyFunctions.php`) a `HTTP_CLIENT_IP` / `HTTP_X_FORWARDED_FOR` fejlécet **kliensről** fogadja el, és ezt írja be az SQL-be – ez tetszőleges kérésfejlécből injektálható érték.

### 1.6 Jelszókezelés

`html/index.php:120` (és szó szerint ugyanez `:168`-on a profil oldalon):

```php
$password = hash('sha256', strip_tags(htmlspecialchars(RealEscapeStringNew($_POST['password']))));
```

Három probléma egyszerre:
1. **Sózatlan, egyszeres SHA-256** – rainbow table és GPU-s brute force ellen nem véd. A kódbázisban sehol nincs `password_hash()` / `password_verify()`.
2. A jelszó `htmlspecialchars()`-on és `strip_tags()`-en megy át hashelés előtt – ez **csendben megcsonkítja** a `<`, `>`, `&`, `"` karaktert tartalmazó jelszavakat.
3. Nincs rate limiting, nincs lockout, nincs időzítés-független összehasonlítás.

### 1.7 Reflektált XSS

Több helyen a `$_GET` közvetlenül, escape nélkül kerül a HTML-be:

```
html/datasheet2.php:179       Search: <?= $_GET["search"];?>
html/admin/episode.php:68     Search: <?= $_GET["search"]; ?>
html/admin/episode.php:245    value="<?= $_GET["edit"]; ?>"
html/admin/uploaders.php:165  Search: <?= $_GET["search"];?>
html/blog.php:283             Search: <?= $_GET["search"];?>
html/episode2.php:78          Search: <?= $_GET["search"]; ?>
```

Nem definiált index esetén ezek ráadásul PHP notice-t is dobnak.

### 1.8 CSRF-védelem sehol nincs

27 `.phtml` fájl tartalmaz `<form>` elemet, **CSRF token egyikben sincs**. Az admin műveletek (törlés, szerkesztés) így egyszerű `<img>`-gel vagy külső oldalról küldött POST-tal kiválthatók, amíg a `userID` süti él (1 év).

Ráadásul több törlő művelet `GET`-en fut: `episode.php?delete=...` (`html/admin/episode.php:284`).

### 1.9 Produkciós hitelesítő adatok a repóban

| Fájl | Tartalom |
|---|---|
| `Config/config.json` | DB host, user, jelszó, adatbázisnév |
| `Config/database.json` | ugyanaz |
| `private/Config/dbconfig.json` | ugyanaz |
| `html/uploaders/Config/database.json` | ugyanaz |
| `html/datasheet2.php:153`, `html/index2.php:18`, `html/full.php:6` | egy **másik** jelszó hardcode-olva |
| ~8 további fájl | ugyanezek kikommentelve, de olvashatóan |

A `.gitignore` ugyan tartalmazza a `.env`-et, de a projekt nem használ `.env`-et – a valódi titkok JSON-ban, verziókövetve vannak.

**Teendő:** minden jelszó cseréje, áttérés `.env`-re, a JSON fájlok törlése és history-tisztítás. A történeti commitokból a jelszó akkor is kinyerhető, ha a fájlt most töröljük – ezért a rotáció nem opcionális.

### 1.10 Hibamegjelenítés élesben

`html/index.php:13` (és további 4 helyen: `:367`, `:613`, `:1594`, `:3515`), `html/ssecret.php:2-4`:

```php
ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);
```

Egyetlen helyen van kikapcsolva (`html/index.php:336`) – tehát nem konfigurációs kérdés, hanem szétszórt, kézzel írt kapcsolók sora.

Ehhez jön, hogy az SQL hiba esetén a kód **kiírja a teljes lekérdezést a böngészőbe**:

```php
exit("<br />SQL Syntax Error! SQL:<br /><pre>" . $sql . "</pre><br />" . $conn->error);
```

Ez sémafelderítést és injection-finomhangolást tesz lehetővé a támadónak.

### 1.11 Egyéb

- `html/uploaders2.php:4`, `html/ep_new.php:130,171` – `shell_exec("clear")` egy webkiszolgálón futó scriptben. Funkciótlan, de `shell_exec` engedélyezettségét feltételezi.
- `private/OtherCode/allInMyFunctions.php:20` – `extract($data)` a view builderben: kontrollálatlan változó-felülírás, ha a `$data` valaha felhasználói inputból épül.
- `html/Newindex.php:16-23` – hardcode-olt IP (`84.0.6.47`) mint hozzáférés-vezérlés a "fansub2" oldalhoz. Ez valójában egy kézzel írt feature flag (lásd 6. fejezet).
- A `Connect::getconn()` (`html/OtherCode/ConnectClass.php:60`) **minden hívásnál új `initConnection()`-t** futtat, tehát a "singleton" nem singleton: kérésenként több tucat MySQL kapcsolat nyílhat. Egyszerre biztonsági (erőforrás-kimerítés) és teljesítmény-probléma.
- `set_charset('utf8')` – a MySQL `utf8` valójában `utf8mb3`, nem kezel 4 bájtos karaktert (emoji, ritka CJK). `utf8mb4` kell.

---

## 2. Architektúra és kódszervezés

### 2.1 Négy párhuzamos generáció

```
html/*.php              (1) régi, önálló scriptek: blog.php, episode2.php, datasheet2.php, shd.php…
html/Views/             (2) első view-szervezés, benne Admin/, Public/Login/, Public/Logout/, Public/Universal/
html/NewViews/          (3) második nekifutás
private/                (4) MVC-kísérlet: Controller/, Model/, View/, Template/Darkly, Template/Simple
```

A `html/index.php:9-25` explicit átkapcsolóval dönti el, melyik generáció fusson:

```php
$newSite = ["fansub", "episodelistnew", "z"];
if (in_array(strtolower($g[0]), $newSite)) { require_once(... "Newindex.php"); exit; }
```

Tehát 3 útvonal az új rendszeren, a többi 19 a régin.

### 2.2 `html/index.php`: 3967 sor, 11 függvény

A fájl ~168 KB. A routing 19 darab egymás után írt `if (isset($g[0]) && strtolower($g[0]) == "...")` blokk. A blokkokon belül keveredik az SQL, az üzleti logika, a külső API-hívás (Jikan) és a HTML. A `getchangelog()` egy 40+ elemű, **kódba írt** tömb, amiből a `VERSION` konstans készül.

Két teljesen azonos törzsű függvény: `siteLogin()` (107) és `siteProfil()` (155) – a profil oldal a login kódjának másolata.

### 2.3 Kódduplikáció

Az `OtherCode/` könyvtár **négyszer** szerepel (`html/`, `html/admin/`, `html/uploaders/`, `private/`), fájlonként 2-4 bitre azonos másolattal:

| Fájl | Példányszám |
|---|---|
| `dencode.function.php`, `print.function.php`, `urlFunction.php` | 4 azonos |
| `stringFunction.php`, `fileFunction.php` | 3 azonos |
| `allInMyFunctions.php`, `databaseFunction.php` | 2-4 enyhén eltérő |

A `Views/` szintén: `html/Views/Public/Login/` és `html/Views/Public/Logout/` 16-16 fájl, jórészt azonos tartalommal – a bejelentkezett/kijelentkezett állapot **külön mappával** van megoldva, nem feltétellel.

Ez a legdrágább tétel a listán: minden hibajavítást 3-4 helyen kell elvégezni, és a gyakorlatban ez sosem történik meg (lásd: az `.htaccess` javítás az egyik mappában megvan, a másikban nincs).

### 2.4 Halott és kísérleti kód a webgyökérben

`html/` alatt élesen elérhető: `test.php`, `test2.php` (23 KB), `test3.php`, `teszt-sanor.php`, `miertnemmukodik.php`, `uploaders2.php`, `index2.php`, `indexb.php` (42 KB), `zindex.php`, `zapi.php`, `ssecret.php`, `ep_new.php`, `re-datasheet.php`, `ntvep.php`, `nx.php`.

Ezek nagy része régi verzió vagy próbálkozás, de **mind kiszolgálható HTTP-n**, és mind tartalmaz adatbázis-hozzáférést. Ez a támadási felület fölösleges megsokszorozása.

### 2.5 Nincs autoloading, nincs névtér

Minden fájl kézi `require_once` láncot használ relatív útvonalakkal (`require_once("../Config/loadConfig.php")`), ami a belépési ponttól függően törik. Nincs `composer.json`, nincs PSR-4, nincs névtér – az összes függvény globális (`Select`, `Insert`, `perm`, `getip`, `logged`…), ami névütközés-veszélyes és tesztelhetetlen.

### 2.6 A router törékeny

`private/OtherCode/router.php:23` – egyetlen sorban ötszörös egymásba ágyazott ternary operátor. Olvashatatlan és karbantarthatatlan. A 26. sor pedig:

```php
if (!empty($method) && function_exists($method)) call_user_func($method);
```

A `$method` az URL második szegmenséből jön. Ez **tetszőleges, paraméter nélküli globális függvény meghívását** engedi az URL-ből – a kód akkor is veszélyes, ha ma épp nincs benne kihasználható célfüggvény.

---

## 3. Hiányzó infrastruktúra

Ami **egyáltalán nincs** a projektben:

| Hiányzik | Következmény |
|---|---|
| `Dockerfile`, `docker-compose.yml` | Nincs reprodukálható környezet |
| `schema.sql` / migrációk | ~37 tábla szerkezete csak az éles DB-ben létezik |
| Seed / demo adat | Üres adatbázison a fejlesztés lehetetlen |
| `composer.json` | Nincs függőségkezelés, nincs autoload |
| `package.json` / bundler | Az asseteket kézzel másolják |
| Tesztek (`phpunit`) | Minden változtatás vakrepülés |
| CI (`.github/workflows/`) | Nincs automatikus ellenőrzés |
| Linter / formázó (`php-cs-fixer`, `phpstan`) | Vegyes stílus, 2 és 3 szóközös behúzás keveredik |
| `.env.example` | Nem derül ki, milyen konfig kell |
| `README` érdemi tartalma | Jelenleg: `# Animem` – 9 bájt |
| `CHANGELOG.md` | A changelog PHP tömbként él a kódban |
| Strukturált naplózás | `echo`-val kiírt hibák |

A PHPMailer használatban van (`use PHPMailer\PHPMailer\SMTP;`), de **Composer nélkül**, kikommentelve – tehát az e-mail küldés jelenleg nem működik.

---

## 4. Adatbázis

### 4.1 Nincs séma a repóban

A kódból visszafejtve legalább **37 tábla** használatban van:

```
users, perm__user, perm__access, perm__site, perm__method
datasheet, datasheet_episodelist, datasheet_uploaders
mal__anime, mal__genres, mal__anime__genres, mal__studios, mal__anime__studios,
mal__type, mal__source, mal__age_rating, mal__studios_type
episodelist, episodelist_links, ep_links, episode_lang, episode_type
links, links3, links_type, uploaders, mininews, anime
statistic__links, statistic__visits_today, statistic__visits_all, statistic_meta,
visits_today_unreg
wp_posts, wp_posts2, wp_links       ← WordPress maradvány
```

**Teendő:** `mysqldump --no-data` az élesből → `docker/mysql/init/01-schema.sql`, majd anonimizált seed → `02-seed.sql`. Innentől minden sémaváltozás migrációs fájl (`migrations/2026_09_10_xxx.sql`), verziózva.

### 4.2 Séma-szintű problémák

- **Duplikált/verziózott táblák**: `links` és `links3`, `wp_posts` és `wp_posts2`, `visits_today_unreg` és `statistic__visits_today` – nem derül ki, melyik az élő.
- **WordPress-maradvány** (`wp_*`): ha nincs használatban, törlendő; ha van, dokumentálandó.
- **JSON oszlop LIKE-kal keresve**: `private/Model/FanSub.php:40` – `WHERE JSON_EXTRACT(D.save,'$.fansub') LIKE '%"{$id}"%'`. Ez sosem tud indexet használni, és a `12` ID hamis találatot ad a `120`-ra is ha a formátum egyszer változik. Helyette normalizált kapcsolótábla kell (`datasheet_fansub`).
- **Unix timestamp INT-ben, stringként hasonlítva**: `WHERE '{$firstTime}' <= create_time` – idézőjelben lévő szám, típuskonverzióval. `DATETIME` + megfelelő index a helyes megoldás.
- **`ORDER BY RAND()`** három helyen (`html/index.php:96`, `:982`, `:3734`, köztük a kezdőlapon) – a `mal__anime`+`datasheet` join teljes eredményhalmazának rendezése minden oldalletöltésnél, 12 sorért. Ez a legdrágább lekérdezés az oldalon.
- Nincs látható index-stratégia, nincs foreign key.
- `utf8` (utf8mb3) charset `utf8mb4` helyett.

---

## 5. Auth: login, regisztráció, session

Ez a felhasználó által külön kért terület. Jelenlegi állapot: **nincs regisztráció sehol** a kódban, a login a fenti sütis megoldás, a `siteProfil()` a login másolata.

### 5.1 Cél-architektúra

```
src/Auth/
├── AuthService.php        login, logout, aktuális felhasználó
├── PasswordHasher.php     password_hash(PASSWORD_ARGON2ID) / password_verify
├── SessionManager.php     PHP session konfiguráció + regeneráció
├── RegistrationService.php  regisztráció + e-mail megerősítés
├── PasswordResetService.php tokenes jelszó-visszaállítás
└── RateLimiter.php        IP + felhasználó szintű próbálkozás-korlát
```

### 5.2 Session-beállítások (kötelező minimum)

```php
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'secure'   => true,       // csak HTTPS
    'httponly' => true,       // JS nem éri el
    'samesite' => 'Lax',      // CSRF alapvédelem
]);
session_start();
```

Bejelentkezéskor `session_regenerate_id(true)` (session fixation ellen), kijelentkezéskor `session_destroy()` + süti törlés.

### 5.3 Jelszókezelés

```php
// regisztráció / jelszócsere
$hash = password_hash($plain, PASSWORD_ARGON2ID);

// bejelentkezés
if (password_verify($plain, $user['password_hash'])) {
    if (password_needs_rehash($user['password_hash'], PASSWORD_ARGON2ID)) {
        // néma újrahashelés az új algoritmussal
    }
}
```

A jelszó **nem megy át** `htmlspecialchars()`-on vagy `strip_tags()`-en. Minimum 12 karakter, és érdemes a [Have I Been Pwned k-anonimity API](https://haveibeenpwned.com/API/v3#PwnedPasswords) ellenőrzést beépíteni.

### 5.4 Migráció a régi jelszavakról

A meglévő `users.password` sózatlan SHA-256. Kétlépcsős átállás:

1. Új oszlop: `password_hash VARCHAR(255) NULL`, `password_algo ENUM('sha256','argon2id')`.
2. Bejelentkezéskor: ha még `sha256`, ellenőrzés a régi módon (`hash_equals()`-szel, nem `==`-vel), siker esetén azonnali újrahashelés Argon2id-vel, a régi oszlop nullázása.
3. Miután minden aktív felhasználó belépett (pl. 6 hónap), a maradék fiókok jelszó-visszaállításra kényszerítése, régi oszlop eldobása.

### 5.5 Regisztráció (új funkció)

- `POST /register` – felhasználónév, e-mail, jelszó
- Validáció: egyediség (DB unique index, nem csak `SELECT`), e-mail formátum, jelszóerősség
- E-mail megerősítés: `users_email_verification` tábla, egyszer használatos, 24 órás token (`random_bytes(32)`, hashelve tárolva)
- Rate limit: IP-nként max 3 regisztráció / óra
- Bot-védelem: hCaptcha vagy honeypot mező
- GDPR: regisztrációkor ÁSZF/adatkezelési elfogadás naplózása időbélyeggel

### 5.6 Kiegészítők

- Jelszó-visszaállítás tokennel (egyszer használatos, 1 órás lejárat, használat után érvénytelenítve)
- "Emlékezz rám" – **külön** selector/validator token táblában, nem a session sütiben
- Opcionális TOTP kétlépcsős azonosítás legalább az admin szerepkörre
- Bejelentkezési napló (IP, user agent, időpont, siker/kudarc) – gyanús aktivitás felderítéséhez

---

## 6. Permission rendszer (RBAC)

### 6.1 Jelenlegi állapot: három párhuzamos, félkész megoldás

1. **Apache Basic Auth** htpasswd fájlokkal (`html/admin/htpasswd/`) – kikommentelve
2. **DB-alapú `perm()` függvény** (`perm__user`, `perm__access`, `perm__site`, `perm__method`) – hamisítható sütire épül, hiányos return-ágakkal
3. **Hardcode-olt IP-szűrés** (`html/Newindex.php:16-23`)

Egyik sincs következetesen kikényszerítve. A `perm()` hívások a view-kban szórványosan jelennek meg, a controller/route szinten nincs központi ellenőrzés – tehát **közvetlen URL-hívással megkerülhetők**.

### 6.2 Cél-architektúra

Megtartható a meglévő `perm__*` táblák logikája (felhasználó → csoport → jogosultság), de tisztázva:

```sql
roles              (id, slug, name, description)
permissions        (id, slug, name, description, category)
role_permissions   (role_id, permission_id)
user_roles         (user_id, role_id, granted_at, granted_by)
```

Jogosultság-elnevezés `erőforrás.művelet` formában, ami olvasható és auditálható:

```
datasheet.view    datasheet.create    datasheet.edit    datasheet.delete
episode.view      episode.create      episode.edit      episode.delete
uploader.manage   user.manage         role.manage       stats.view
admin.access      featureflag.manage
```

Javasolt alap-szerepkörök: `guest` (nem bejelentkezett), `user`, `uploader`, `moderator`, `admin`.

### 6.3 Kikényszerítés – "mindenhez", egy helyen

A lényeg nem a táblaszerkezet, hanem hogy **ne lehessen megkerülni**. Ezért a jogosultság-ellenőrzés a routing rétegbe kerül, nem a view-ba:

```php
// routes.php – a jog az útvonal definíciójának része, nem opcionális kiegészítés
$router->get('/admin/datasheet',        [DataSheetController::class, 'index'])->can('datasheet.view');
$router->post('/admin/datasheet/{id}',  [DataSheetController::class, 'update'])->can('datasheet.edit');
```

Ehhez:
- **Deny-by-default**: aminek nincs `can()` megjelölése, az csak explicit `->public()` jelöléssel érhető el – így az elfelejtett jogosultság hibát okoz, nem lyukat.
- Az `AuthorizationMiddleware` minden kérésnél lefut, a controller elé.
- Sablonban csak *megjelenítés-vezérlésre* használjuk (`@can('datasheet.edit')`), **soha nem védelemként**.
- Objektumszintű ellenőrzés ott, ahol kell (pl. egy uploader csak a saját feltöltéseit szerkesztheti) – `Policy` osztályokkal.
- Minden jogosultsági döntés naplózva (ki, mit, mikor, engedélyezve/megtagadva).

---

## 7. Feature flag rendszer

Jelenleg a "kapcsolók" így néznek ki: kikommentelt kódblokkok, `$newSite = ["fansub", ...]` tömb (`html/index.php:11`), és egy hardcode-olt IP (`html/Newindex.php:22`, `$newSite` a `html/index.php:9`-en). Ezek mind fordítási idejű, deploy-t igénylő megoldások.

### 7.1 Cél

```sql
CREATE TABLE feature_flags (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  flag_key      VARCHAR(100)  NOT NULL UNIQUE,
  name          VARCHAR(255)  NOT NULL,
  description   TEXT,
  enabled       TINYINT(1)    NOT NULL DEFAULT 0,
  rollout_pct   TINYINT UNSIGNED NOT NULL DEFAULT 0,   -- 0-100 fokozatos bevezetés
  strategy      ENUM('off','on','percentage','role','user_list','ip_list') NOT NULL DEFAULT 'off',
  payload       JSON NULL,                              -- szerepkörök / user ID-k / IP-k
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

```php
if (Feature::enabled('new_fansub_page')) {
    return $this->newFansubPage();
}
return $this->legacyFansubPage();
```

### 7.2 Feloldási sorrend

1. `.env` felülbírálás (`FEATURE_NEW_FANSUB_PAGE=true`) – lokális fejlesztéshez
2. Adatbázis rekord (admin felületről kapcsolható, deploy nélkül)
3. Alapértelmezés a kódban (`false`)

Az eredmény kérésenként cache-elve (egy lekérdezés az összes flagre, nem flagenként egy).

### 7.3 Azonnal kiváltható meglévő kapcsolók

| Jelenlegi | Flag |
|---|---|
| `$newSite = [...]` (`html/index.php:9`) | `new_router.enabled` + oldalankénti flag |
| `Newindex.php` IP-szűrés (`84.0.6.47`) | `fansub2_preview` (strategy: `ip_list`) |
| Kikommentelt Jikan API blokkok | `jikan_enrichment` |
| Kikommentelt PHPMailer | `email_notifications` |
| Kikommentelt `otamoon` webhook | `otamoon_sync` |
| Kivezetett témaváltó | `theme_switcher` |

Ezzel a "van egy félkész új verzió, de nem merjük bekapcsolni" helyzet – ami az egész kódbázis alapproblémája – kezelhetővé válik: az új oldal 0%-on bemegy éles kódba, majd 5% → 50% → 100%.

---

## 8. Javasolt mappastruktúra

```
animem/
├── docker/
│   ├── php/Dockerfile              PHP 8.3-FPM + szükséges kiterjesztések
│   ├── nginx/default.conf
│   └── mysql/init/
│       ├── 01-schema.sql
│       └── 02-seed.sql
├── docker-compose.yml              fejlesztői környezet
├── docker-compose.prod.yml         éles felülbírálások
├── Makefile                        make up / make test / make migrate
├── .env.example
├── composer.json
│
├── public/                         ← AZ EGYETLEN webgyökér
│   ├── index.php                   egyetlen belépési pont
│   ├── .htaccess
│   └── assets/                     buildelt CSS/JS (nem verziózva)
│
├── src/                            PSR-4: Animem\
│   ├── Kernel.php
│   ├── Http/
│   │   ├── Router.php
│   │   ├── Request.php  Response.php
│   │   ├── Controller/             HomeController, DataSheetController, EpisodeController…
│   │   └── Middleware/             Authenticate, Authorize, CsrfProtection, RateLimit
│   ├── Auth/                       (5. fejezet)
│   ├── Authorization/              PermissionRegistry, Policy/
│   ├── Feature/                    FeatureFlag, FeatureRepository
│   ├── Domain/
│   │   ├── Anime/                  Entity + Repository + Service
│   │   ├── Episode/
│   │   ├── Uploader/
│   │   ├── User/
│   │   └── Statistics/
│   ├── Database/
│   │   ├── Connection.php          PDO, valódi singleton, prepared statements
│   │   ├── QueryBuilder.php
│   │   └── Migration/
│   ├── Integration/                JikanClient, IndavideoClient
│   └── Support/                    Config, Logger, Str, Url
│
├── templates/                      ← minden .phtml egy helyen
│   ├── layouts/     base, admin
│   ├── partials/    header, navbar, footer, sidebar
│   ├── pages/       home, datasheet, episode, fansub, search
│   └── admin/
│
├── resources/                      forrás assetek (SCSS, JS), buildelendő
├── migrations/                     időbélyeges SQL migrációk
├── storage/                        logs/, cache/, uploads/   ← git-ignorált
├── tests/
│   ├── Unit/  Integration/  Feature/
└── docs/
    ├── FEJLESZTESI-TERV.md         (ez a fájl)
    ├── ARCHITECTURE.md
    └── DEPLOYMENT.md
```

**A legfontosabb változás**: csak a `public/` van a webgyökérben. A jelenlegi `html/` mappában PHP forrás, konfiguráció, htpasswd fájlok és 56 MB feltöltött tartalom keveredik – mind kiszolgálható HTTP-n. Az új struktúrában a `src/`, `templates/`, `migrations/` fizikailag elérhetetlen a böngészőből.

---

## 9. Egy parancsos indítás

Cél: friss klón után

```bash
cp .env.example .env && docker compose up
```

és az oldal fut a `http://localhost:8080`-on, feltöltött sémával és demo adattal.

### 9.1 `docker-compose.yml` (vázlat)

```yaml
services:
  nginx:
    image: nginx:1.27-alpine
    ports: ["8080:80"]
    volumes:
      - ./public:/var/www/html/public:ro
      - ./docker/nginx/default.conf:/etc/nginx/conf.d/default.conf:ro
    depends_on: [php]

  php:
    build: ./docker/php
    volumes:
      - .:/var/www/html
      - ./storage:/var/www/html/storage
    environment:
      DB_HOST: mysql
      DB_NAME: ${DB_NAME}
      DB_USER: ${DB_USER}
      DB_PASSWORD: ${DB_PASSWORD}
      APP_ENV: ${APP_ENV:-local}
      APP_DEBUG: ${APP_DEBUG:-true}
    depends_on:
      mysql: { condition: service_healthy }

  mysql:
    image: mysql:8.0
    environment:
      MYSQL_ROOT_PASSWORD: ${DB_ROOT_PASSWORD}
      MYSQL_DATABASE: ${DB_NAME}
      MYSQL_USER: ${DB_USER}
      MYSQL_PASSWORD: ${DB_PASSWORD}
    volumes:
      - mysql-data:/var/lib/mysql
      - ./docker/mysql/init:/docker-entrypoint-initdb.d:ro   # induláskor lefut
    healthcheck:
      test: ["CMD", "mysqladmin", "ping", "-h", "localhost"]
      interval: 5s
      retries: 10

  # fejlesztéshez, éles profilból kihagyva
  adminer:
    image: adminer:latest
    ports: ["8081:8080"]
    profiles: [dev]

  mailpit:                # kimenő e-mail elkapása fejlesztéskor
    image: axllent/mailpit
    ports: ["8025:8025"]
    profiles: [dev]

volumes:
  mysql-data:
```

A `docker-entrypoint-initdb.d` a kulcs: a MySQL konténer **első indításkor magától lefuttatja** a `01-schema.sql` és `02-seed.sql` fájlt. Nincs kézi importálás.

### 9.2 `docker/php/Dockerfile` (vázlat)

```dockerfile
FROM php:8.3-fpm-alpine

RUN apk add --no-cache icu-dev libzip-dev oniguruma-dev \
 && docker-php-ext-install pdo_mysql mysqli intl zip opcache

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY composer.json composer.lock ./
RUN composer install --no-scripts --no-autoloader --prefer-dist
COPY . .
RUN composer dump-autoload --optimize
```

Élesre külön stage `--no-dev` telepítéssel és bekapcsolt OPcache `validate_timestamps=0` beállítással.

### 9.3 `Makefile`

```makefile
up:       ## Indítás fejlesztői módban
	docker compose --profile dev up -d
down:
	docker compose down
migrate:
	docker compose exec php php bin/migrate.php
seed:
	docker compose exec php php bin/seed.php
test:
	docker compose exec php vendor/bin/phpunit
lint:
	docker compose exec php vendor/bin/php-cs-fixer fix --dry-run --diff
stan:
	docker compose exec php vendor/bin/phpstan analyse src --level=6
shell:
	docker compose exec php sh
fresh:    ## Adatbázis nulláról
	docker compose down -v && docker compose --profile dev up -d
```

---

## 10. Frontend és teljesítmény

- **jQuery-alapú, 1.5 MB jQuery + pluginek** (`jquery-migrate` jelenléte azt jelzi, hogy 1.x-ről 3.x-re történt frissítés után is maradtak elavult hívások). A `jquery-migrate` figyelmeztetéseit érdemes végigjavítani, majd a migrate csomagot eldobni.
- **Font Awesome 5.15.2 kicsomagolva (19 MB) *és* zipként (5.3 MB)** ugyanabban a mappában – a zip törlendő, a kicsomagolt könyvtárból pedig csak a ténylegesen használt ikonok kellenek (subsetting), vagy CDN.
- **1333 SVG fájl** a repóban (Font Awesome ikonkészlet) – ebből tipikusan 20-30-at használ egy oldal.
- **Nincs asset build**: nincs minifikálás, bundling, cache-busting hash. Minden deploy után a felhasználók régi CSS/JS-t kaphatnak.
- **Nincs lazy loading a képeknél**, nincs `WebP`/`AVIF` változat – az 56 MB `Assets/uploads` jórészt borítókép.
- **Nincs HTTP cache fejléc-stratégia** a statikus fájlokra.
- **Nincs CSP, X-Frame-Options, X-Content-Type-Options, HSTS** fejléc.
- **Nincs oldalszintű cache**: a kezdőlap `ORDER BY RAND()` lekérdezése minden látogatónál lefut. Egy 60 másodperces cache a látogatottság többszörösét elbírná ugyanazon a hardveren.
- `header.phtml` abszolút URL-ekkel hivatkozik saját assetekre (`https://animem.org/Assets/...`) – ezért a lokális fejlesztés az éles szerver fájljait tölti be. Relatív vagy konfigból jövő `ASSET_URL` kell.
- **Nincs reszponzív ellenőrzés / akadálymentesítés**: a Bootstrap adott, de a saját `.phtml`-ekben nincs `alt` attribútum a képek nagy részén, és nincs `aria` jelölés.

---

## 11. Repo higiénia

| Probléma | Méret / hatás |
|---|---|
| `html/Assets/uploads/` verziózva | 56 MB – felhasználói tartalom nem való gitbe (S3 / kötet / CDN kell) |
| `html/Assets/fontawesome-...-web.zip` | 5.3 MB, a kicsomagolt változat mellett |
| `html/bigSave.json`, `Dai_Project_List.json` | adatdump a kódban |
| Titkok a history-ban | `git filter-repo` + jelszórotáció |
| Egyetlen commit (`Initial commit`) | 10 év fejlesztés története elveszett; innentől érdemes értelmes commitokat írni |
| Nincs branch-stratégia, nincs PR-folyamat | – |
| Vegyes behúzás (2 és 3 szóköz) | `.editorconfig` + `php-cs-fixer` |
| Magyar és angol azonosítók keverve | Egy nyelv választása (kódban angol, UI-ban magyar) |
| Fájlnevek: `piciadminnemelbaszni.htpasswd`, `miertnemmukodik.php`, `teszt-sanor.php` | Törlendők a takarítás során |

A `.gitignore` jelenleg 4 sor. Bővítendő: `/vendor/`, `/storage/`, `/public/assets/build/`, `*.sql.gz`, `.idea/`, `.vscode/`, `.DS_Store`.

---

## 12. Ütemterv

### P0 – Vérzéscsillapítás (1-2 hét, éles rendszeren)

Sorrend számít, mert az 1-2. pont nélkül a többinek nincs értelme.

1. **Minden jelszó rotálása** (DB user, admin fiókok, a `datasheet2.php`-ban lévő második jelszó)
2. **`/admin` és `/uploaders` lezárása** – Basic Auth visszakapcsolása ideiglenesen, helyes htpasswd útvonallal, a webgyökéren kívülre helyezett fájllal
3. Süti-alapú auth kiváltása PHP session-nel (`userID` süti megszüntetése)
4. `display_errors` kikapcsolása élesben, SQL hibaüzenetek naplóba, nem böngészőbe
5. Halott fájlok törlése a webgyökérből (`test*.php`, `index2.php`, `indexb.php`, `zindex.php`, `miertnemmukodik.php`, `uploaders2.php`, `ep_new.php`, `teszt-sanor.php`)
6. `htpasswd` fájlok és `Config/*.json` törlése a repóból + git history tisztítás
7. XSS javítás a 6 azonosított helyen (`htmlspecialchars(..., ENT_QUOTES, 'UTF-8')`)
8. CSRF token bevezetése minden állapotváltó űrlapon; törlő műveletek `GET`-ről `POST`-ra

### P1 – Alap infrastruktúra (2-4 hét, fejlesztői oldalon)

9. `composer.json` + PSR-4 autoload + PHPMailer rendes telepítése
10. `.env` + `Config` osztály (a JSON fájlok kiváltása)
11. **Séma kimentése** → `docker/mysql/init/01-schema.sql` + anonimizált seed
12. **Docker Compose + Dockerfile + Makefile** → `docker compose up` működik
13. PDO connection réteg **prepared statementekkel**, a `Select/Insert/Update/Delete` függvények kiváltása
14. `README.md` megírása (indítás, felépítés, közreműködés)
15. PHPStan (level 4-től indulva) + php-cs-fixer + GitHub Actions CI
16. Első tesztek: auth, permission, router

### P2 – Fokozatos újraírás (folyamatos, feature flagek mögött)

17. **Feature flag rendszer** – ez az első, mert ez teszi biztonságossá az összes többit
18. Auth modul: regisztráció, e-mail megerősítés, jelszó-visszaállítás, jelszó-migráció
19. RBAC modul: `roles`/`permissions` táblák, route-szintű kikényszerítés, admin felület a szerepkörökhöz
20. Router + Kernel: az `index.php` 19 `if`-blokkjának kiváltása
21. Oldalankénti migráció az új struktúrába, flag mögött, 0% → 100% bevezetéssel: kezdőlap → adatlap → epizódlista → fansub → kereső → admin
22. `OtherCode/` négy másolatának összevonása egy `src/Support/`-ba
23. `Views` / `NewViews` / `Template` összevonása egy `templates/`-be, a Login/Logout mappaduplikáció megszüntetése feltétellel
24. Adatbázis-tisztítás: `links3`, `wp_posts2`, `visits_today_unreg` sorsának eldöntése; indexek; `utf8mb4`; JSON-LIKE keresés kiváltása kapcsolótáblával
25. Asset pipeline (Vite vagy esbuild), Font Awesome subsetting, képoptimalizálás
26. Cache-réteg (Redis vagy fájl) a kezdőlapra és a statisztikákra
27. Uploads kiköltöztetése objektumtárolóba / kötetre

---

## 13. Amit érdemes megtartani

Nem minden rossz a kódbázisban, és a refaktor során ezekre lehet építeni:

- A `private/` MVC-kísérlet **iránya jó** (Controller/Model/View szétválasztás, `viewBuilder()` kompozíciós megközelítés, `Template/Darkly` + `Template/Simple` témarendszer). Ezt érdemes befejezni, nem eldobni.
- A `perm__*` táblák **adatmodellje** (felhasználó → csoport → jogosultság, külön `site`/`method` szinttel) lényegében egy működő RBAC terv – csak a kikényszerítés hiányzik mellőle.
- A `Config/loadConfig.php` `DbConfig` osztálya már elindult a központosított konfiguráció felé.
- A `getchangelog()` tartalma valódi, hasznos verziótörténet – csak nem kódban a helye.
- A Jikan API integráció (`html/index.php:359`) működő adatgazdagítás, érdemes külön `Integration/JikanClient` osztályba emelni retry és cache logikával.

---

## 14. Kockázatok

| Kockázat | Kezelés |
|---|---|
| Nincs séma → a Docker környezet nem tud elindulni | P1/11 az egész terv előfeltétele; ha az éles DB nem érhető el, a sémát a kódból kell visszafejteni |
| Nincs teszt → a refaktor csendben tör el dolgokat | Először karakterizációs tesztek a fő oldalakra (HTTP státusz + kulcselemek), csak utána nyúlni a kódhoz |
| Jelszó-rotáció kizárhat aktív adminokat | Előre egyeztetett időablak, dokumentált visszaállás |
| A feature flag mögötti dupla kód átmenetileg növeli a karbantartási terhet | Minden flaghez lejárati dátum; 100%-os bevezetés után a régi ág **törlése**, nem kikommentelése |
| A git history tisztítása minden klónt érvénytelenít | Előre bejelentett időpont, mindenki újraklónoz |
