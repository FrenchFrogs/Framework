<?php namespace FrenchFrogs\Form\Element;


class Submit extends Element
{

    /**
     * Constructeur
     *
     * @param $name
     * @param string $label
     * @param array $attr
     */
    public function __construct($name, $attr = [] )
    {
        $this->setAttribute($attr);
        $this->setName($name);
        $this->addAttribute('type', 'submit');
    }

    /**
     * @return string
     */
    public function __toString()
    {

        $render = '';
        try {
            $render = $this->getRenderer()->render('form.submit', $this);
        } catch(\Exception $e){
            dd($e->getMessage());
        }

        return $render;

    }
}