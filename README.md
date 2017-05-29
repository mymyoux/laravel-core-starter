# laravel-core-starter
Helpful module for laravel project

## Translation

### Syntax in vue templates

#### Example in template.vue: 

* **((key))** will be translate from **template.key** - PHP Side
* **((custom.key))** will be translate from **custom.key** - PHP Side
* **((\*key))** will use key as a variable and will be transformed into **trad(key)**
* **(('static.'+key))** will use key as a variable and will be transformed into **trad('static.'+key)**
* **((key,count))** will be transformed into **trad('template.key', count)**. If count is a plain number, it will be translated from PHP side. Count value will be used to determine singular/plurial form.
* **((key,{'json_key':'value'}))** will be transformed into **trad('template.key', {'json_key':'value'})**

* They are all combinables
* If you want to use json object + count value, use smart_count key inside the json object to set the count value
* If a key has no '.' **app.** will be used as prefix to make sur that all keys have at least one dot

#### Examples

    <div>((key))</div>
    <span :class='((*key))'></span> //if not translated in PHP you need to use :attribute syntax
    <span class='((key))'></span> //no need for static translations

### Syntax in translations

* `{{count}} octopus` => **count** will be used to determine singular/plurial + will be replaced by the count value
* `{{person.name}}'s bag`=> **person.name** will be replaced if a json object with person key is used

