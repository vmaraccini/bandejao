<?

require_once("bandex.php");

$bandex = new Bandex();

$options = array(
	'days' => -1,
	'meals' => -1
);

echo "<h3>Cardápio do bandejão central para a refeição atual</h3>";
echo "<pre>";
print_r($bandex->get('central', $options));
echo "</pre>";