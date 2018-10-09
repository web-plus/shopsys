# Domain Settings

Při práci se Shopsys Frameworkem je možné se setkat s pojmy - doména, multidoménovost, multijazyčnost.
V tomto dokumentu se pokusíme objasnit co tyto pojmy znamenají a jak s nimi pracovat při vývoji vašeho projektu.

## Doména, multidoménovost, multijazyčnost

- **Doména** - Doménu je možné chápat jako jednu instanci dat eshopu. 
Některá data mužou být mezi doménami sdílená.
Na jednotlivé domény přistupujeme prostřednictvím individuálních url adres.

- **Multidoménový atribut** - Hodnota tohoto atributu muže být nastavena ruzně pro každou doménu.
Příkladem multidoménového atributu je výchozí cenová skupina pro nepřihlášeného zákazníka, tato muže být ruzná pro každou doménu.
Příkladem nemultidoménového atributu je ean produktu, tento je stejný pro všechny domény. 

- **Multijazyčný atribut** - Hodnota tohoto atributu muže být nastavena ruzně pro každý jazyk.
Příkladem multijazyčného atributu je název produktu.
Příkladem nemultijazyčného atributu je cena produktu. 

- **Rozdíl mezi multidoménovým a multijazyčným atributem** - Hodnota multijazyčného atributu bude stejná pro každou doménu se stejným jazykem.
Např v případě, že je pro jazyk *en* nastaven název produktu *A4tech mouse*, bude tento název stejný pro všechny domény, které mají nastaven jazyk *en*.
V případě multidoménového atributu je možné nastavovat ruzné hodnoty pro ruzné domény bez ohledu na jazyk

*Pozn: Demodata, které jsou na Shopsys Frameworku k dispozici obsahují data jen v cz a en jazyce*

## Nastavovaní a práce s doménami

### 1. Vytvoření aplikace s jednou doménou (s cz jazykem)
Nejedná se o odstranění domény z již běžícího projektu.

#### 1.1 Konfigurace domény
Upravte konfiguraci domény v `app/config/domains.yml`.
V tomto souboru se nastavuje ID domény, identifikátor domény pro doménové záložky v administraci, jazyk domény.

#### 1.2 Konfigurace url adresy
Nastavte požadovanou url adresu domény v `app/config/domains_urls.yml`.

#### 1.3 Nastavevení projektu jako jednodoménový
Upravte nastavení parametru `is-multidomain` v `build.xml` na hodnotu `false`.
Prostřednictvím této změny se upraví chování některých phingových targetu - např při importu demo dat se aplikace neppokusí importovat zbytečně data pro další doménu.

#### 1.4 Přegenerování databázových funkcí pro prací s jazykem
V rámci databázových funkcí je nutno přegenerovat výchozí databázové funkce pro práci s jazykem, které jsou vytvořeny ve výchozím stavu pro `en` jazyk.
Vytvořte si migraci s nasledujícími dotazy, kde `xx` nahraďte za kód jazyka

```
    $this->sql('CREATE OR REPLACE FUNCTION get_domain_ids_by_locale(locale text) RETURNS SETOF integer AS $$
        BEGIN
            CASE
                WHEN locale = \'xx\' THEN RETURN NEXT 1;
                ELSE RAISE EXCEPTION \'Locale % does not exists\', locale;
            END CASE;
        END
        $$ LANGUAGE plpgsql IMMUTABLE;');
        
    $this->sql('CREATE OR REPLACE FUNCTION get_domain_locale(domain_id integer) RETURNS text AS $$
        BEGIN
            CASE
                WHEN domain_id = 1 THEN RETURN \'xx\';
                ELSE RAISE EXCEPTION \'Domain with ID % does not exists\', domain_id;
            END CASE;
        END
    $$ LANGUAGE plpgsql IMMUTABLE;');

```


#### 1.5 Build
Po proběhnutí buildu je již vytvořena jednodoménová aplikace.

### 2. Přidání nové domény (s jazykem, který je již použit u jiné domény)

#### 2.1 Konfigurace domény
Přidejte konfiguraci nové domény do `app/config/domains.yml`.
V tomto souboru se nastavuje ID domény, identifikátor domény pro doménové záložky v administraci, jazyk domény.

#### 2.2 Konfigurace url adresy
Nastavte url adresu nové domény v `app/config/domains_urls.yml`.

*Pozn: V případě, že přidávate novou doménu na platformě Mac, musíte tuto doménu povolit i v interfasu networku, viz https://github.com/shopsys/shopsys/blob/master/docs/installation/installation-using-docker-macos.md#11-enable-second-domain-optional

#### 2.3 Nastavení multidoménových atributu 
Pro správne fungování nové domény je nutno nastavit hodnoty multidoménových atributu pro tuto novou doménu.
Pro nastavení hodnot multidoménových atributu spusťte phingový target
```
php phing create-domains-data
```
Při běhu se pro novou doménu nakopírují hodnoty multidoménových atributu z 1. domény, viz `Shopsys/FrameworkBundle/Component/Domain/DomainDataCreator.php`, ve kterém je nadefinována konstanta `TEMPLATE_DOMAIN_ID`.
V případě, že je u přidávané domény nastaven i nový jazyk, dojde při spuštění targetu i k vytvoření překladových záznamu pro tento nový jazyk - tyto záznamy ale budou prázdné.
Při běhu je pro novou doménu vytvořena i jedna cenová skupina s názvem Default.
Součástí targetu je i spuštění automatických přepočtu cen, dostupností, a viditelností produktu.

#### 2.4 Vygenerování souboru pro dizajn nové domény
K správnemu zobrazení nové domény je nutno vygenerovat assets
```
php phing grunt
```

### 3. Přidání nové domény (s jazykem, který ještě není použit u jiné domény)

#### 3.1 Konfigurace domény
Přidejte konfiguraci nové domény do `app/config/domains.yml`.
V tomto souboru se nastavuje ID domény, identifikátor domény pro doménové záložky v administraci, jazyk domény.

#### 3.2 Konfigurace url adresy
Nastavte url adresu nové domény v `app/config/domains_urls.yml`.

*Pozn: V případě, že přidávate novou doménu na platformě Mac, musíte tuto doménu povolit i v interfasu networku, viz https://github.com/shopsys/shopsys/blob/master/docs/installation/installation-using-docker-macos.md#11-enable-second-domain-optional

#### 3.3 Routy pro nový jazyk
Vytvořte soubor s routami v `ShopBundle/Resources/config/` podle vzoru `routing_front_xx.yml` kde `xx` je kód nového jazyka.
Novou konfiguraci pro routy je nutno naimportovat v `app/config/packages/shopsys_shop.yml`

#### 3.4 Zprávy a popisky
Pro správně zobrazení popisky typu *Registrace*, *Košík*, vytvořte pro nový jazyk překlady zpráv v `ShopBundle/Resources/translations/`.

#### 3.5 Nastavení multidoménových atributu 
Pro správne fungování nové domény je nutno nastavit hodnoty multidoménových atributu pro tuto novou doménu.
Pro nastavení hodnot multidoménových atributu spusťte phingový target
```
php phing create-domains-data
```
Při běhu se pro novou doménu nakopírují hodnoty multidoménových atributu z 1. domény, viz `Shopsys/FrameworkBundle/Component/Domain/DomainDataCreator.php`, ve kterém je nadefinována konstanta `TEMPLATE_DOMAIN_ID`.
V případě, že je u přidávané domény nastaven i nový jazyk, dojde při spuštění targetu i k vytvoření překladových záznamu pro tento nový jazyk - tyto záznamy ale budou prázdné.
Při běhu je pro novou doménu vytvořena i jedna cenová skupina s názvem Default.
Součástí targetu je i spuštění automatických přepočtu cen, dostupností, a viditelností produktu.

#### 3.6 Vygenerování souboru pro dizajn nové domény
K správnemu zobrazení nové domény je nutno vygenerovat assets
```
php phing grunt
```
V této chvíli se již nová doména funkční, neobsahuje ale žádná data protože nejsou vyplněny multijazyčné atribúty produktu, oddělení, ..., pro nový jazyk.

### 4. Změna url adresy existující domény

#### 4.1 Konfigurace url adresy
Změňte url adresu domény v `app/config/domains_urls.yml`.

*Pozn: V případě, že nastavujete novou doménu na platformě Mac, musíte tuto doménu povolit i v interfasu networku, viz https://github.com/shopsys/shopsys/blob/master/docs/installation/installation-using-docker-macos.md#11-enable-second-domain-optional

### 4.2 Nahrazení staré url adresy
Spusťte phingový target
```
php phing replace-domains-urls
```
Spuštění tohoto příkazu zajistí nahrazení všech výskytu staré url adresy v textových atributech v databázi za novou url adresu.

### 5. Změna jazyka u jednodoménového eshopu

#### 5.1 Změna jazyka v konfiguraci domény
Změnte jazyk v konfiguraci domény v `app/config/domains.yml`.

#### 5.2 Přegenerování databázových funkcí pro prací s jazykem
V rámci databázových funkcí je nutno přegenerovat výchozí databázové funkce pro práci s jazykem, které jsou vytvořeny ve výchozím stavu pro `en` jazyk.
Vytvořte si migraci s nasledujícími dotazy, kde `xx` nahraďte za kód jazyka

```
    $this->sql('CREATE OR REPLACE FUNCTION get_domain_ids_by_locale(locale text) RETURNS SETOF integer AS $$
        BEGIN
            CASE
                WHEN locale = \'xx\' THEN RETURN NEXT 1;
                ELSE RAISE EXCEPTION \'Locale % does not exists\', locale;
            END CASE;
        END
        $$ LANGUAGE plpgsql IMMUTABLE;');
        
    $this->sql('CREATE OR REPLACE FUNCTION get_domain_locale(domain_id integer) RETURNS text AS $$
        BEGIN
            CASE
                WHEN domain_id = 1 THEN RETURN \'xx\';
                ELSE RAISE EXCEPTION \'Domain with ID % does not exists\', domain_id;
            END CASE;
        END
    $$ LANGUAGE plpgsql IMMUTABLE;');

```

