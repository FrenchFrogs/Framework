<?php

use FrenchFrogs\Core\Configurator;

if (!function_exists('html')) {
    /**
     * Render an HTML tag
     *
     * @param $tag
     * @param array $attributes
     * @param string $content
     * @return string
     */
    function html($tag, $attributes = [], $content = '')
    {
        $autoclosed = [
            'area', 'base', 'br', 'col', 'command', 'embed', 'hr', 'img', 'input',
            'keygen', 'link', 'meta', 'param', 'source', 'track', 'wbr'
        ];

        // Attributes
        foreach ($attributes as $key => &$value) {
            $value = sprintf('%s="%s"', $key, str_replace('"', '&quot;', $value)) . ' ';
        }
        $attributes = implode(' ', $attributes);

        return array_search($tag, $autoclosed) === false ? sprintf('<%s %s>%s</%1$s>', $tag, $attributes, $content) : sprintf('<%s %s/>', $tag, $attributes);
    }
}


/**
 * Debug => die function
 *
 * dd => debug die
 *
 *
 */
if (!function_exists('dd')) {

    function dd()
    {
        array_map(function($x) { !d($x); }, func_get_args());
        d(microtime(),'Stats execution');
        die;
    }
}


if (! function_exists('d')) {
    /**
     * Dump the passed variables and end the script.
     *
     * @param  mixed
     * @return void
     */
    function d()
    {
        array_map(function ($x) {
            (new Illuminate\Support\Debug\Dumper)->dump($x);
        }, func_get_args());
    }
}

/**
 * Return human format octet size (mo, go etc...)
 *
 * @param unknown_type $size
 * @param unknown_type $round
 * @throws Exception
 */
function human_size($size, $round = 1) {

    $unit = array('Ko', 'Mo', 'Go', 'To');

    // initialisation du resultat
    $result = $size . 'o';

    // calcul
    foreach ($unit as $u) {if (($size /= 1024) > 1) {$result = round($size, $round) . $u;}}

    return $result;
}



/**
 * Return the namespace configurator
 *
 * @param null $namespace
 * @return \FrenchFrogs\Core\Configurator
 */
function configurator($namespace = null)
{
    return Configurator::getInstance($namespace);
}


/**
 * Return new panel polliwog instance
 *
 * @param ...$args
 * @return FrenchFrogs\Panel\Panel\Panel
 */
function panel(...$args)
{
    // retrieve the good class
    $class = configurator()->get('panel.class', FrenchFrogs\Panel\Panel\Panel::class);

    // build the instance
    $reflection = new ReflectionClass($class);
    return $reflection->newInstanceArgs($args);
}

/**
 * Return new table polliwog instance
 *
 * @param ...$args
 * @return FrenchFrogs\Table\Table\Table
 */
function table(...$args)
{
    // retrieve the good class
    $class = configurator()->get('table.class', FrenchFrogs\Table\Table\Table::class);

    // build the instance
    $reflection = new ReflectionClass($class);
    return $reflection->newInstanceArgs($args);
}

/**
 * Return a new form polliwog instance
 *
 * @param ...$args
 * @return  FrenchFrogs\Form\Form\Form
 */
function form(...$args)
{
    // retrieve the good class
    $class = configurator()->get('form.class', FrenchFrogs\Form\Form\Form::class);

    // build the instance
    $reflection = new ReflectionClass($class);
    return $reflection->newInstanceArgs($args);
}

/**
 * Return new modal polliwog
 *
 * @param ...$args
 * @return FrenchFrogs\modal\Modal\Modal
 */
function modal(...$args)
{
    // retrieve the good class
    $class = configurator()->get('modal.class', FrenchFrogs\Modal\Modal\Modal::class);

    // build the instance
    $reflection = new ReflectionClass($class);
    return $reflection->newInstanceArgs($args);
}

/**
 * Return a Javascript Container polliwog
 *
 * @param $namespace
 * @param null $selector
 * @param null $function
 * @param ...$params
 * @return \FrenchFrogs\Container\Javascript
 */
function js($namespace = null, $selector = null, $function = null, ...$params){
    /** @var $container FrenchFrogs\Container\Javascript */
    $container = FrenchFrogs\Container\Javascript::getInstance($namespace);

    if (!is_null($function)){
        array_unshift($params, $selector, $function);
        call_user_func_array([$container, 'appendJs'], $params);
    } elseif(!is_null($selector)) {
        $container->append($selector);
    }

    return $container;
}


/**
 * Return css cointainer
 *
 * @param null $href
 * @return \FrenchFrogs\Container\Css
 */
function css($namespace  = null) {
    return FrenchFrogs\Container\Css::getInstance($namespace);
}


/**
 * Return a head container
 *
 * @param $name
 * @param $value
 * @param null $conditional
 * @return $this
 */
function h($name = null, $value = null, $conditional = null) {
    /** @var $container FrenchFrogs\Container\Head */
    $container = FrenchFrogs\Container\Head::getInstance();

    if (!is_null($name)) {
        $container->meta($name, $value, $conditional);
    }
    return $container;
}

/**
 * Return action form url
 *
 * @param $controller
 * @param string $action
 * @param array $params
 * @return string
 */
function action_url($controller, $action = 'getIndex', $params = [], $query = [])
{
    $controller = substr($controller, 0,3) == 'App' ?  '\\' . $controller : $controller;
    return URL::action($controller . '@' . $action, $params) . (empty($query) ? '' : ('?' . http_build_query($query)));
}


/**
 * Return ruler polliwog
 *
 * @return \FrenchFrogs\Ruler\Ruler\Ruler
 */
function ruler()
{
    // retrieve the good class
    $class = configurator()->get('ruler.class', FrenchFrogs\Ruler\Ruler\Ruler::class);

    return $class::getInstance();
}

/**
 *
 *
 * @param array ...$params
 * @return \Illuminate\Database\Query\Expression
 */
function raw(...$params) {
    return DB::raw(...$params);
}

/**
 * shortcut for transaction
 *
 *
 * @param $callable
 * @param null $connection
 * @return mixed
 * @throws \Exception
 * @throws \Throwable
 */
function transaction($callable, $connection = null)
{
    if (is_null($connection)) {
        return DB::transaction($callable);
    } else {
        return DB::connection($connection)->transaction($callable);
    }
}

/**
 * Query Builder
 *
 * @param $table
 * @param array $columns
 * @return Illuminate\Database\Query\Builder
 */
function query($table, $columns = null, $connection = null) {

    $query = DB::connection($connection)->table($table);

    if (!is_null($columns)) {
        $query->addSelect($columns);
    }

    return $query;
}

/**
 * Generation ou formatage d'un uuid
 *
 * @param string $format
 * @param null $uuid
 * @return NULL|number|string
 * @throws \Exception
 */
function uuid($format = 'bytes', $uuid = null) {
    if(is_null($uuid)){
        $uuid = Uuid::generate(4)->$format;
    }else{
        $uuid = Uuid::import($uuid)->$format;
    }
    return $uuid;
}


/**
 * Filter value
 *
 * @param $value
 * @param $filters
 */
function f($value, $filters) {
    $filter = new \FrenchFrogs\Filterer\Filterer();
    $filter->setFilters($filters);
    return $filter->filter($value);
}

/**
 * Validate value
 *
 * @param $value
 * @param $validators
 * @return bool
 */
function v($value, $validators) {
    $validator = Validator::make(['v' => $value], ['v' => $validators]);
    return !$validator->fails();
}

/**
 * Return the filtered value if correct, else return null
 *
 * @param $value
 * @param null $filters
 * @param null $validators
 * @return mixed|null
 */
function fv($value, $filters = null, $validators = null) {

    if (!is_null($filters)) {
        $value = f($value, $filters);
    }

    if (!is_null($validators)) {

        if (!v($value, $validators)) {
            $value = null;
        }
    }

    return $value;
}


/**
 * Return true is application is in debug mode
 *
 * @return mixed
 */
function debug() {
    return config('app.debug');
}

/**
 * return true if application is in production mode
 *
 * @return bool
 */
function production(){
    return app()->environment() == 'production';
}

