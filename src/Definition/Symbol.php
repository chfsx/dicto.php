<?php
/******************************************************************************
 * An implementation of dicto (scg.unibe.ch/dicto) in and for PHP.
 *
 * Copyright (c) 2016 Richard Klees <richard.klees@rwth-aachen.de>
 * 
 * This software is licensed under The MIT License. You should have received
 * a copy of the license along with the code.
 */

namespace Lechimp\Dicto\Definition;

/**
 * A symbol in known to the parser.
 */
class Symbol {
    /**
     * @var string
     */
    protected $regexp;

    /**
     * @var int
     */
    protected $binding_power;

    public function __construct($regexp, $binding_power) {
        assert('is_string($regexp)');
        if (!is_string($regexp) || @preg_match("%$regexp%", "") === false) {
            throw new \InvalidArgumentException("Invalid regexp: '%$regexp%'");
        }
        assert('is_int($binding_power)');
        assert('$binding_power >= 0');
        $this->regexp = $regexp;
        $this->binding_power = $binding_power;
    }

    /**
     * @return  string
     */
    public function regexp() {
        return $this->regexp;
    }

    /**
     * @return  int
     */
    public function binding_power() {
        return $this->binding_power;
    }
}