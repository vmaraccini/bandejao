# Bandejão

API em PHP para obter os cardápios dos bandejões da USP de Butantã.

### Uso básico

Para retornar o cardápio do restaurante da Química, por exemplo, basta escrever
```php
get('quimica'); // Bandejão da Química
get('fisica,prefeitura'); // Bandejões da Física e da Prefeitura
```

Os restaurantes podem ser `central`, `fisica`, `prefeitura`, `quimica` ou `clube`.


### Opções

A função `get` assume um segundo parâmetro, opcional, de opções:
```php
get($restaurants, $options);
```

Onde `$options` é uma array com os possíveis parâmetros:

* `days`: array com os índices dos dias cujos cardápios serão retornados, variando de `0` (segunda-feira) até `6` (domingo). Caso seja `-1`, retorna o índice do dia atual;
* `meals`: array com os índices das refeições cujos cardápios serão retornados, assumindo `0` (almoço) ou `1` (jantar). Caso seja `-1`, retorna o índice da refeição atual;
* `time_format`: formato de tempo, segundo as [convenções em PHP] [date], para os dias da semana;
* `meal_format`: formato utilizado para as identificar as refeições, podendo ser `numeric`;
* `implode`: se `TRUE`, compacta os resultados em arrays de um elemento, apenas.


### Retornar saldo

Para visualizar o saldo disponível na carteirinha USP , basta escrever
```php
balance($nusp, $password);
```

Onde:

* `$nusp`: número USP do usuário
* `$password`: senha do usuário na página do [Rucard] [rucard]


### Servidor dedicado
 
Você pode utilizar diretamente a API através do [servidor dedicado] [api]. As funções são homônimas às da classe e os parâmetros são passados via GET. Exemplos:

```
http://api-bitcluster.rhcloud.com/bandejao/get?restaurants=quimica
http://api-bitcluster.rhcloud.com/bandejao/balance?nusp=123456789&pass=senha
```

[date]: http://php.net/manual/en/function.date.php
[rucard]: https://uspdigital.usp.br/rucard
[api]: http://api-bitcluster.rhcloud.com


Créditos a Denis Isidoro por todo o trabalho pesado
