<?php namespace FrenchFrogs\Form\Element;


class Text extends Element
{

    /**
     * Constructeur
     *
     * @param $name
     * @param string $label
     * @param array $attr
     */
    public function __construct($name, $label = '', $attr = [] )
    {
        $this->setAttribute($attr);
        $this->setName($name);
        $this->setLabel($label);
        $this->addAttribute('type', 'text');
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     *
     * @param mixed $value
     */
    public function setValue($value)
    {
        $this->value = $value;
        $this->addAttribute('value', $value);



        return $this;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        $render = '';
        try {
            $render = $this->getRenderer()->render('form.text', $this);
        } catch(\Exception $e){
            dd($e->getMessage());
        }

        return $render;
    }
}