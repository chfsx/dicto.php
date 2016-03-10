<?php
/******************************************************************************
 * An implementation of dicto (scg.unibe.ch/dicto) in and for PHP.
 * 
 * Copyright (c) 2016, 2015 Richard Klees <richard.klees@rwth-aachen.de>
 *
 * This software is licensed under The MIT License. You should have received 
 * a copy of the along with the code.
 */

use Lechimp\Dicto\Dicto as Dicto;
use Lechimp\Dicto\Definition as Def;

class RuleDefinitionTest extends PHPUnit_Framework_TestCase {
    public function test_variable_class() {
        $var = Dicto::_every()->_class();
        $this->assertInstanceOf("\\Lechimp\\Dicto\\Definition\\_Variable", $var);
    } 

    public function test_variable_function() {
        $var = Dicto::_every()->_function();
        $this->assertInstanceOf("\\Lechimp\\Dicto\\Definition\_Variable", $var);
    }

    /**
     * @dataProvider same_base_variable_2tuple_provider 
     */
    public function test_variable_and(Def\_Variable $left, Def\_Variable $right) {
        $var = $left->_and($right);
        $this->assertInstanceOf("\\Lechimp\\Dicto\\Definition\\_Variable", $var);
    }

    /**
     * @dataProvider same_base_variable_2tuple_provider 
     */
    public function test_variable_except(Def\_Variable $left, Def\_Variable $right) {
        $var = $left->_except($right);
        $this->assertInstanceOf("\\Lechimp\\Dicto\\Definition\\_Variable", $var);
    }

    public function test_variable_buildin() {
        $var = Dicto::_every()->_buildin();
        $this->assertInstanceOf("\\Lechimp\\Dicto\\Definition\\_Variable", $var);
    }

    public function test_variable_global() {
        $var = Dicto::_every()->_global();
        $this->assertInstanceOf("\\Lechimp\\Dicto\\Definition\\_Variable", $var);
    }

    public function test_variable_file() {
        $var = Dicto::_every()->_file();
        $this->assertInstanceOf("\\Lechimp\\Dicto\\Definition\\_Variable", $var);
    }

    /**
     * @dataProvider all_base_variables_provider 
     */
    public function test_variable_with_name(Def\_Variable $var) {
        $named = $var->_with()->_name("foo.*");
        $this->assertInstanceOf("\\Lechimp\\Dicto\\Definition\\_Variable", $var);
    }

    /**
     * @dataProvider different_base_variable_2tuple_provider
     */
    public function test_and_only_works_on_same_type($l, $r) {
        try {
            $l->_and($r);
            $this->assertFalse("This should not happen.");
        }
        catch (\InvalidArgumentException $_) {};
    }

    /**
     * @dataProvider different_base_variable_2tuple_provider 
     */
    public function test_except_only_works_on_same_type($l, $r) {
        try {
            $l->_except($r);
            $this->assertFalse("This should not happen.");
        }
        catch (\InvalidArgumentException $_) {};
    }


    /**
     * @dataProvider all_base_variables_provider
     */
    public function test_explain_variables($var) {
        $var2 = $var->explain("EXPLANATION");
        $this->assertEquals(get_class($var), get_class($var2));
    }

    /**
     * @dataProvider some_rules_provider
     */
    public function test_explain_rules($rule) {
        $rule2 = $rule->explain("EXPLANATION");
        $this->assertEquals(get_class($rule), get_class($rule2));
    }

    public function same_base_variable_2tuple_provider() {
        $ls = $this->all_base_variables_provider();
        $rs = $this->all_base_variables_provider();
        $amount = count($ls);
        assert($amount == count($rs));
        $ret = array();
        for($i = 0; $i < $amount; $i++) {
            $l = $ls[$i];
            $r = $rs[$i];
            $ret[] = array($l[0], $r[0]);
        }
        return $ret;
    }

    public function different_base_variable_2tuple_provider() {
        $ls = $this->all_base_variables_provider();
        $rs = $this->all_base_variables_provider();
        $ret = array();
        foreach ($ls as $l) {
            foreach ($rs as $r) {
                if (get_class($l[0]) === get_class($r[0])) {
                    continue;
                }
                $ret[] = array($l[0], $r[0]);
            }
        }
        return $ret;
    }

    public function base_variable_2tuple_provider() {
        $ls = $this->all_base_variables_provider();
        $rs = $this->all_base_variables_provider();
        $ret = array();
        foreach ($ls as $l) {
            foreach ($rs as $r) {
                $ret[] = array($l[0], $r[0]);
            }
        }
        return $ret;
    }

    public function all_base_variables_provider() {
        return array
            ( array(Dicto::_every()->_class())
            , array(Dicto::_every()->_function())
            , array(Dicto::_every()->_global())
            , array(Dicto::_every()->_file())
            , array(Dicto::_every()->_buildin())
            );
    }

    public function some_rules_provider() {
        $vars = $this->base_variable_2tuple_provider();
        $ret = array();
        foreach ($vars as $tup) {
            list($l, $r) = $tup;
            $ret[] = array($l->cannot()->invoke($r));
            $ret[] = array($l->cannot()->depend_on($r));
            $ret[] = array($l->must()->depend_on($r));
            $ret[] = array(Dicto::only($l)->can()->depend_on($r));
            $ret[] = array($l->cannot()->contain_text("Foo"));
        }
        return $ret;
    }
}
