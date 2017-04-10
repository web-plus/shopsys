# Multidomain Features
**TODO: better name?**

Shopsys Framework is built with multi-channel support. Front-end may be accessed on different domains resulting in differadministrationnents.

## Domains Configuration
Basic domain attributes are configured in `/app/config/domains.yml`. This file is committed into VCS as it is important to share this configuration across development team:

```yaml
domains:
  - id: 1                           # ID is an integer used as a reference in the application
    name: shopsys                   # Name of domain is used only in administration for easier identification
    locale: en                      # Locale (language) of the domain for static texts and translatable entities

  - id: 2
    name: 2.shopsys
    locale: cs
    styles_directory: domain2       # Optional attribute defining front-end styles directory in /src/Shopsys/ShopBundle/Resources/styles/front
```

Every domain has its own URL under which it is accessible, configured in `/app/config/domains_urls.yml`. This file is ignored by VCS because it differs when run locally in development environment or in production. Configuration template can be found in `/app/config/domains_urls.yml.dist`. 

### Domain-dependant Settings
Additional configuration of domains can be found in administration - setting that may be changed in run-time: default pricing group, used currency, SEO title, etc.

This setting is done by `\Shopsys\ShopBundle\Component\Setting\Setting` class.

## Multidomain Entities
**TODO:**

Some entities are dependant on domain. Eg. Article.

Some entities have attributes that are dependant on Domain. For example the same product can have different description on every domain (to avoid duplicate content and possible disadvantage in search results).

Some entities have translatable attributes (see AbstractTranslatableEntity). Eg. Product.

