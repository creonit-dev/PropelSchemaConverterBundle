# PropelSchemaConverterBundle
Allows use YAML to describe schema

# Installation

With composer :

``` json
{
    "require": {
        "creonit/propel-schema-converter-bundle": "dev-master"
    }
}
```

# Usage
Create a file `BundleDir/Resources/config/schema.yml` and run the command to build the model or migration. File schema.xml will be created automatically.

## Example `schema.yml`

``` yaml
config:
    required: true

database:
  +:
    name: default
    namespace: AppBundle\Model
    package: src.AppBundle.Model
    defaultIdMethod: native

  +behavior:
    - auto_add_pk
    
  table:
    +: {allowPkInsert: true}
    id: {type: INTEGER, size: 10, primaryKey: true, autoIncrement: true}
    column: varchar(64)
    column2: int
    column3: text key(8)
    column4: tinyint uniq
    column5: bool = 1
    column6: - datetime
    +unique:
      - column(32)
    +index: [column2]
    +behavior: 
      - timestampable
      - sortable: {use_scope: true, scope_column: column2}
    
  table2:
    id: int(10) ~pk
    table_id: int
    column: text = Default value
    column2: int
    +foreign-key:
      - table: {local: table_id, foreign: id}
    +behavior: 
      - timestampable
      - sortable: column2
    
  table3: 
    table_id: int pk > table.id
    table2_id: int pk > table2.id(restrict cascade)
    
```
