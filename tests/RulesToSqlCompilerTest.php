<?php
/******************************************************************************
 * An implementation of dicto (scg.unibe.ch/dicto) in and for PHP.
 *
 * Copyright (c) 2016, 2015 Richard Klees <richard.klees@rwth-aachen.de>
 *
 * This software is licensed under The MIT License. You should have received
 * a copy of the licence along with the code.
 */

use Lechimp\Dicto as Dicto;
use Lechimp\Dicto\Analysis\RulesToSqlCompiler;
use Lechimp\Dicto\Variables\Variable;
use Lechimp\Dicto\Definition as Def;
use Lechimp\Dicto\Rules as Rules;
use Lechimp\Dicto\Variables as Vars;
use Lechimp\Dicto\App\DB;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;

class RulesToSqlCompilerTest extends PHPUnit_Framework_TestCase {
    public function setUp() {
        $this->connection = DriverManager::getConnection
            ( array
                ( "driver" => "pdo_sqlite"
                , "memory" => true
                )
            ); 
        $this->db = new DB($this->connection);
        $this->db->init_sqlite_regexp();
        $this->db->maybe_init_database_schema();
    }


    // All classes cannot contain text "foo".

    public function all_classes_cannot_contain_text_foo() {
        return new Rules\Rule
            ( Rules\Rule::MODE_CANNOT
            , new Vars\Classes("allClasses")
            , new Rules\ContainText()
            , array("foo")
            );
    }

    public function test_all_classes_cannot_contain_text_foo_1() {
        $rule = $this->all_classes_cannot_contain_text_foo();
        $id = $this->db->entity(Variable::CLASS_TYPE, "AClass", "file", 1, 2, "foo");
        $stmt = $rule->compile($this->db);

        $this->assertInstanceOf("\\Doctrine\\DBAL\\Driver\\Statement", $stmt);

        $res = $stmt->fetchAll();
        $expected = array
            ( array
                ( "entity_id"   => "$id"
                , "file"        => "file"
                , "source"      => "foo"
                )
            );
        $this->assertEquals($expected, $res);
    }

    public function test_all_classes_cannot_contain_text_foo_2() {
        $rule = $this->all_classes_cannot_contain_text_foo();
        $id = $this->db->entity(Variable::CLASS_TYPE, "AClass", "file", 1, 2, "bar");
        $stmt = $rule->compile($this->db);

        $this->assertInstanceOf("\\Doctrine\\DBAL\\Driver\\Statement", $stmt);

        $res = $stmt->fetchAll();
        $this->assertEquals(array(), $res);
    }

    public function test_all_classes_cannot_contain_text_foo_3() {
        $rule = $this->all_classes_cannot_contain_text_foo();
        $id = $this->db->entity(Variable::FUNCTION_TYPE, "AClass", "file", 1, 2, "foo");
        $stmt = $rule->compile($this->db);

        $this->assertInstanceOf("\\Doctrine\\DBAL\\Driver\\Statement", $stmt);

        $res = $stmt->fetchAll();
        $this->assertEquals(array(), $res);
    }


    // All classes cannot depend on globals.

    public function all_classes_cannot_depend_on_globals() {
        return new Rules\Rule
            ( Rules\Rule::MODE_CANNOT
            , new Vars\Classes("allClasses")
            , new Rules\DependOn()
            , array(new Vars\Globals("allGlobals"))
            );
    }

    public function test_all_classes_cannot_depend_on_globals_1() {
        $rule = $this->all_classes_cannot_depend_on_globals();
        $id1 = $this->db->entity(Variable::CLASS_TYPE, "AClass", "file", 1, 2, "foo");
        $id2 = $this->db->reference(Variable::GLOBAL_TYPE, "glob", "file", 2);
        $this->db->relation("depend_on", $id1, $id2, "file", 2, "a line");
        $stmt = $rule->compile($this->db);

        $this->assertInstanceOf("\\Doctrine\\DBAL\\Driver\\Statement", $stmt);

        $res = $stmt->fetchAll();
        $expected = array
            ( array
                ( "entity_id"       => "$id1"
                , "reference_id"    => "$id2"
                , "file"            => "file"
                , "line"            => 2
                , "source"          => "a line"
                )
            );
        $this->assertEquals($expected, $res);
    }

    public function test_all_classes_cannot_depend_on_globals_2() {
        $rule = $this->all_classes_cannot_depend_on_globals();
        $id = $this->db->entity(Variable::CLASS_TYPE, "AClass", "file", 1, 2, "bar");
        $id2 = $this->db->reference(Variable::GLOBAL_TYPE, "glob", "file", 2);
        $stmt = $rule->compile($this->db);

        $this->assertInstanceOf("\\Doctrine\\DBAL\\Driver\\Statement", $stmt);

        $res = $stmt->fetchAll();
        $this->assertEquals(array(), $res);
    }

    public function test_all_classes_cannot_depend_on_globals_3() {
        $rule = $this->all_classes_cannot_depend_on_globals();
        $id1 = $this->db->entity(Variable::FUNCTION_TYPE, "a_function", "file", 1, 2, "foo");
        $id2 = $this->db->reference(Variable::GLOBAL_TYPE, "glob", "file", 2);
        $this->db->relation("depend_on", $id1, $id2, "file", 2, "a line");
        $stmt = $rule->compile($this->db);

        $this->assertInstanceOf("\\Doctrine\\DBAL\\Driver\\Statement", $stmt);

        $res = $stmt->fetchAll();
        $this->assertEquals(array(), $res);
    }


    // All classes cannot invoke functions.

    public function all_classes_cannot_invoke_functions() {
        return new Rules\Rule
            ( Rules\Rule::MODE_CANNOT
            , new Vars\Classes("allClasses")
            , new Rules\Invoke()
            , array(new Vars\Functions("allFunctions"))
            );
    }

    public function test_all_classes_cannot_invoke_functions_1() {
        $rule = $this->all_classes_cannot_invoke_functions();
        $id1 = $this->db->entity(Variable::CLASS_TYPE, "AClass", "file", 1, 2, "foo");
        $id2 = $this->db->reference(Variable::FUNCTION_TYPE, "a_function", "file", 2);
        $this->db->relation("invoke", $id1, $id2, "file", 2, "a line");
        $stmt = $rule->compile($this->db);

        $this->assertInstanceOf("\\Doctrine\\DBAL\\Driver\\Statement", $stmt);

        $res = $stmt->fetchAll();
        $expected = array
            ( array
                ( "entity_id"       => "$id1"
                , "reference_id"    => "$id2"
                , "file"            => "file"
                , "line"            => 2
                , "source"          => "a line"
                )
            );
        $this->assertEquals($expected, $res);
    }

    public function test_all_classes_cannot_invoke_functions_2() {
        $rule = $this->all_classes_cannot_invoke_functions();
        $id = $this->db->entity(Variable::CLASS_TYPE, "AClass", "file", 1, 2, "bar");
        $id2 = $this->db->reference(Variable::FUNCTION_TYPE, "a_function", "file", 2);
        $stmt = $rule->compile($this->db);

        $this->assertInstanceOf("\\Doctrine\\DBAL\\Driver\\Statement", $stmt);

        $res = $stmt->fetchAll();
        $this->assertEquals(array(), $res);
    }

    public function test_all_classes_cannot_invoke_functions_3() {
        $rule = $this->all_classes_cannot_invoke_functions();
        $id1 = $this->db->entity(Variable::FUNCTION_TYPE, "some_function", "file", 1, 2, "foo");
        $id2 = $this->db->reference(Variable::FUNCTION_TYPE, "a_function", "file", 2);
        $this->db->relation("invoke", $id1, $id2, "file", 2, "a line");
        $stmt = $rule->compile($this->db);

        $this->assertInstanceOf("\\Doctrine\\DBAL\\Driver\\Statement", $stmt);
        $res = $stmt->fetchAll();
        $this->assertEquals(array(), $res);
    }


    // Everything cannot depend on error suppressor.

    public function everything_cannot_depend_on_error_suppressor() {
        return new Rules\Rule
            ( Rules\Rule::MODE_CANNOT
            , new Vars\Everything("everything")
            , new Rules\DependOn()
            , array(new Vars\LanguageConstruct("errorSuppressor", "@"))
            );
    }

    /**
     * @dataProvider entity_types_provider
     */
    public function test_everything_cannot_depend_on_error_suppressor_1($type) {
        $rule = $this->everything_cannot_depend_on_error_suppressor();
        $id1 = $this->db->entity($type, "entity", "file", 1, 2, "foo");
        $id2 = $this->db->reference(Variable::LANGUAGE_CONSTRUCT_TYPE, "@", "file", 2);
        $this->db->relation("depend_on", $id1, $id2, "file", 2, "a line");
        $stmt = $rule->compile($this->db);

        $this->assertInstanceOf("\\Doctrine\\DBAL\\Driver\\Statement", $stmt);

        $res = $stmt->fetchAll();
        $expected = array
            ( array
                ( "entity_id"       => "$id1"
                , "reference_id"    => "$id2"
                , "file"            => "file"
                , "line"            => 2
                , "source"          => "a line"
                )
            );
        $this->assertEquals($expected, $res);
    }

    public function entity_types_provider() {
        return array
            ( array(Variable::CLASS_TYPE)
            , array(Variable::FILE_TYPE)
            , array(Variable::FUNCTION_TYPE)
            , array(Variable::METHOD_TYPE)
            );
    }

    public function test_everything_cannot_depend_on_error_suppressor_2() {
        $rule = $this->everything_cannot_depend_on_error_suppressor();
        $id1 = $this->db->entity(Variable::CLASS_TYPE, "AClass", "file", 1, 2, "foo");
        $id2 = $this->db->reference(Variable::LANGUAGE_CONSTRUCT_TYPE, "unset", "file", 2);
        $this->db->relation("depend_on", $id1, $id2, "file", 2, "a line");
        $stmt = $rule->compile($this->db);

        $this->assertInstanceOf("\\Doctrine\\DBAL\\Driver\\Statement", $stmt);

        $res = $stmt->fetchAll();
        $this->assertEquals(array(), $res);
    }


    // AClasses cannot depend on globals.

    public function a_classes_cannot_depend_on_globals() {
        return new Rules\Rule
            ( Rules\Rule::MODE_CANNOT
            , new Vars\WithName
                ( "AClass"
                , new Vars\Classes("AClasses")
                )
            , new Rules\DependOn()
            , array(new Vars\Globals("allGlobals"))
            );
    }

    public function test_a_classes_cannot_depend_on_globals_1() {
        $rule = $this->a_classes_cannot_depend_on_globals();
        $id1 = $this->db->entity(Variable::CLASS_TYPE, "AClass", "file", 1, 2, "foo");
        $id2 = $this->db->reference(Variable::GLOBAL_TYPE, "glob", "file", 2);
        $this->db->relation("depend_on", $id1, $id2, "file", 2, "a line");
        $stmt = $rule->compile($this->db);

        $this->assertInstanceOf("\\Doctrine\\DBAL\\Driver\\Statement", $stmt);

        $res = $stmt->fetchAll();
        $expected = array
            ( array
                ( "entity_id"       => "$id1"
                , "reference_id"    => "$id2"
                , "file"            => "file"
                , "line"            => 2
                , "source"          => "a line"
                )
            );
        $this->assertEquals($expected, $res);
    }

    public function test_a_classes_cannot_depend_on_globals_2() {
        $rule = $this->a_classes_cannot_depend_on_globals();
        $id1 = $this->db->entity(Variable::CLASS_TYPE, "BClass", "file", 1, 2, "foo");
        $id2 = $this->db->reference(Variable::GLOBAL_TYPE, "glob", "file", 2);
        $this->db->relation("depend_on", $id1, $id2, "file", 2, "a line");
        $stmt = $rule->compile($this->db);

        $this->assertInstanceOf("\\Doctrine\\DBAL\\Driver\\Statement", $stmt);

        $res = $stmt->fetchAll();
        $this->assertEquals(array(), $res);
    }


    // All classes cannot depend on globals with name "glob".

    public function all_classes_cannot_depend_on_glob() {
        return new Rules\Rule
            ( Rules\Rule::MODE_CANNOT
            , new Vars\Classes("allClasses")
            , new Rules\DependOn()
            , array(new Vars\WithName
                ( "glob"
                , new Vars\Globals("glob")
                ))
            );
    }

    public function test_all_classes_cannot_depend_on_glob_1() {
        $rule = $this->all_classes_cannot_depend_on_glob();
        $id1 = $this->db->entity(Variable::CLASS_TYPE, "AClass", "file", 1, 2, "foo");
        $id2 = $this->db->reference(Variable::GLOBAL_TYPE, "glob", "file", 2);
        $this->db->relation("depend_on", $id1, $id2, "file", 2, "a line");
        $stmt = $rule->compile($this->db);

        $this->assertInstanceOf("\\Doctrine\\DBAL\\Driver\\Statement", $stmt);

        $res = $stmt->fetchAll();
        $expected = array
            ( array
                ( "entity_id"       => "$id1"
                , "reference_id"    => "$id2"
                , "file"            => "file"
                , "line"            => 2
                , "source"          => "a line"
                )
            );
        $this->assertEquals($expected, $res);
    }

    public function test_all_classes_cannot_depend_on_glob_2() {
        $rule = $this->all_classes_cannot_depend_on_glob();
        $id1 = $this->db->entity(Variable::CLASS_TYPE, "AClass", "file", 1, 2, "foo");
        $id2 = $this->db->reference(Variable::GLOBAL_TYPE, "another_glob", "file", 2);
        $this->db->relation("depend_on", $id1, $id2, "file", 2, "a line");
        $stmt = $rule->compile($this->db);

        $this->assertInstanceOf("\\Doctrine\\DBAL\\Driver\\Statement", $stmt);

        $res = $stmt->fetchAll();
        $this->assertEquals(array(), $res);
    }


    // Everything but a classes cannot deppend on error suppressor.

    public function everything_but_a_classes_cannot_depend_on_error_suppressor() {
        return new Rules\Rule
            ( Rules\Rule::MODE_CANNOT
            , new Vars\ButNot
                ( "but_AClasses"
                , new Vars\Everything("everything")
                , new Vars\WithName
                    ( "AClass"
                    , new Vars\Classes("AClasses")
                    )
                )
            , new Rules\DependOn
            , array(new Vars\LanguageConstruct("errorSuppressor", "@"))
            );
    }

    public function test_but_not_1() {
        $rule = $this->everything_but_a_classes_cannot_depend_on_error_suppressor();
        $id1 = $this->db->entity(Variable::CLASS_TYPE, "SomeClass", "file", 1, 2, "foo");
        $id2 = $this->db->reference(Variable::LANGUAGE_CONSTRUCT_TYPE, "@", "file", 2);
        $this->db->relation("depend_on", $id1, $id2, "file", 2, "a line");
        $stmt = $rule->compile($this->db);

        $this->assertInstanceOf("\\Doctrine\\DBAL\\Driver\\Statement", $stmt);

        $res = $stmt->fetchAll();
        $expected = array
            ( array
                ( "entity_id"       => "$id1"
                , "reference_id"    => "$id2"
                , "file"            => "file"
                , "line"            => 2
                , "source"          => "a line"
                )
            );
        $this->assertEquals($expected, $res);
    }

    public function test_but_not_2() {
        $rule = $this->everything_but_a_classes_cannot_depend_on_error_suppressor();
        $id1 = $this->db->entity(Variable::CLASS_TYPE, "AClass", "file", 1, 2, "foo");
        $id2 = $this->db->reference(Variable::LANGUAGE_CONSTRUCT_TYPE, "@", "file", 2);
        $this->db->relation("depend_on", $id1, $id2, "file", 2, "a line");
        $stmt = $rule->compile($this->db);

        $this->assertInstanceOf("\\Doctrine\\DBAL\\Driver\\Statement", $stmt);

        $res = $stmt->fetchAll();
        $this->assertEquals(array(), $res);
    }


    // All classes as well as all functions cannot depend on globals.

    public function all_classes_as_well_as_all_functions_cannot_depend_on_globals() {
        return new Rules\Rule
            ( Rules\Rule::MODE_CANNOT
            , new Vars\AsWellAs
                ( "AllClassesAsWellAsAllFunctions"
                , new Vars\Classes("allClasses")
                , new Vars\Functions("allFunctions")
                )
            , new Rules\DependOn()
            , array(new Vars\Globals("allGlobals"))
            );
    }

    public function test_as_well_as_1() {
        $rule = $this->all_classes_as_well_as_all_functions_cannot_depend_on_globals();
        $id1 = $this->db->entity(Variable::CLASS_TYPE, "AClass", "file", 1, 2, "foo");
        $id2 = $this->db->reference(Variable::GLOBAL_TYPE, "glob", "file", 2);
        $this->db->relation("depend_on", $id1, $id2, "file", 2, "a line");
        $stmt = $rule->compile($this->db);

        $this->assertInstanceOf("\\Doctrine\\DBAL\\Driver\\Statement", $stmt);

        $res = $stmt->fetchAll();
        $expected = array
            ( array
                ( "entity_id"       => "$id1"
                , "reference_id"    => "$id2"
                , "file"            => "file"
                , "line"            => 2
                , "source"          => "a line"
                )
            );
        $this->assertEquals($expected, $res);
    }

    public function test_as_well_as_2() {
        $rule = $this->all_classes_as_well_as_all_functions_cannot_depend_on_globals();
        $id1 = $this->db->entity(Variable::FUNCTION_TYPE, "a_function", "file", 1, 2, "foo");
        $id2 = $this->db->reference(Variable::GLOBAL_TYPE, "glob", "file", 2);
        $this->db->relation("depend_on", $id1, $id2, "file", 2, "a line");
        $stmt = $rule->compile($this->db);

        $this->assertInstanceOf("\\Doctrine\\DBAL\\Driver\\Statement", $stmt);

        $res = $stmt->fetchAll();
        $expected = array
            ( array
                ( "entity_id"       => "$id1"
                , "reference_id"    => "$id2"
                , "file"            => "file"
                , "line"            => 2
                , "source"          => "a line"
                )
            );
        $this->assertEquals($expected, $res);
    }

    public function test_as_well_as_3() {
        $rule = $this->all_classes_as_well_as_all_functions_cannot_depend_on_globals();
        $id1 = $this->db->entity(Variable::METHOD_TYPE, "a_method", "file", 1, 2, "foo");
        $id2 = $this->db->reference(Variable::GLOBAL_TYPE, "glob", "file", 2);
        $this->db->relation("depend_on", $id1, $id2, "file", 2, "a line");
        $stmt = $rule->compile($this->db);

        $this->assertInstanceOf("\\Doctrine\\DBAL\\Driver\\Statement", $stmt);

        $res = $stmt->fetchAll();
        $this->assertEquals(array(), $res);
    }


    // Everything but AClasses must depend on globals.

    public function everything_but_a_classes_must_depend_on_globals() {
        return new Rules\Rule
            ( Rules\Rule::MODE_MUST
            , new Vars\ButNot
                ( "but_AClasses"
                , new Vars\Everything("everything")
                , new Vars\WithName
                    ( "AClass"
                    , new Vars\Classes("AClasses")
                    )
                )
            , new Rules\DependOn()
            , array(new Vars\Globals("allGlobals"))
            );
    }

    public function test_must_depend_on_1() {
        $rule = $this->everything_but_a_classes_must_depend_on_globals();
        $id1 = $this->db->entity(Variable::METHOD_TYPE, "a_method", "file", 1, 2, "foo");
        $id2 = $this->db->reference(Variable::GLOBAL_TYPE, "glob", "file", 2);
        $stmt = $rule->compile($this->db);

        $this->assertInstanceOf("\\Doctrine\\DBAL\\Driver\\Statement", $stmt);

        $res = $stmt->fetchAll();
        $expected = array
            ( array
                ( "entity_id"   => "$id1"
                , "file"        => "file"
                , "line"        => "1"
                , "source"      => "foo"
                )
            );
        $this->assertEquals($expected, $res);
    }

    public function test_must_depend_on_2() {
        $rule = $this->everything_but_a_classes_must_depend_on_globals();
        $id1 = $this->db->entity(Variable::FUNCTION_TYPE, "AClass", "file", 1, 2, "foo");
        $id2 = $this->db->reference(Variable::GLOBAL_TYPE, "glob", "file", 2);
        $stmt = $rule->compile($this->db);

        $this->assertInstanceOf("\\Doctrine\\DBAL\\Driver\\Statement", $stmt);

        $res = $stmt->fetchAll();
        $expected = array
            ( array
                ( "entity_id"   => "$id1"
                , "file"        => "file"
                , "line"        => "1"
                , "source"      => "foo"
                )
            );
        $this->assertEquals($expected, $res);
    }

    public function test_must_depend_on_3() {
        $rule = $this->everything_but_a_classes_must_depend_on_globals();
        $id1 = $this->db->entity(Variable::FUNCTION_TYPE, "AClass", "file", 1, 2, "foo");
        $id2 = $this->db->reference(Variable::GLOBAL_TYPE, "glob", "file", 2);
        $this->db->relation("depend_on", $id1, $id2, "file", 2, "a line");
        $stmt = $rule->compile($this->db);

        $this->assertInstanceOf("\\Doctrine\\DBAL\\Driver\\Statement", $stmt);

        $res = $stmt->fetchAll();
        $this->assertEquals(array(), $res);
    }

    public function test_must_depend_on_4() {
        $rule = $this->everything_but_a_classes_must_depend_on_globals();
        $id1 = $this->db->entity(Variable::CLASS_TYPE, "AClass", "file", 1, 2, "foo");
        $id2 = $this->db->reference(Variable::GLOBAL_TYPE, "glob", "file", 2);
        $stmt = $rule->compile($this->db);

        $this->assertInstanceOf("\\Doctrine\\DBAL\\Driver\\Statement", $stmt);

        $res = $stmt->fetchAll();
        $this->assertEquals(array(), $res);
    }

    public function test_must_depend_on_5() {
        $rule = $this->everything_but_a_classes_must_depend_on_globals();
        $id1 = $this->db->entity(Variable::CLASS_TYPE, "BClass", "file", 1, 2, "foo");
        $id2 = $this->db->reference(Variable::GLOBAL_TYPE, "glob", "file", 2);
        $stmt = $rule->compile($this->db);

        $this->assertInstanceOf("\\Doctrine\\DBAL\\Driver\\Statement", $stmt);

        $res = $stmt->fetchAll();
        $expected = array
            ( array
                ( "entity_id"   => "$id1"
                , "file"        => "file"
                , "line"        => "1"
                , "source"      => "foo"
                )
            );
        $this->assertEquals($expected, $res);
    }
    // Only AClasses can depend on globals.

    public function only_a_classes_can_depend_on_globals() {
        return new Rules\Rule
            ( Rules\Rule::MODE_ONLY_CAN
            , new Vars\WithName
                ( "AClass"
                , new Vars\Classes("AClasses")
                )
            , new Rules\DependOn()
            , array(new Vars\Globals("allGlobals"))
            );
    }

    public function test_only_can_depend_on_1() {
        $rule = $this->only_a_classes_can_depend_on_globals();
        $id1 = $this->db->entity(Variable::METHOD_TYPE, "a_method", "file", 1, 2, "foo");
        $id2 = $this->db->reference(Variable::GLOBAL_TYPE, "glob", "file", 2);
        $this->db->relation("depend_on", $id1, $id2, "file", 2, "a line");
        $stmt = $rule->compile($this->db);

        $this->assertInstanceOf("\\Doctrine\\DBAL\\Driver\\Statement", $stmt);

        $res = $stmt->fetchAll();
        $expected = array
            ( array
                ( "entity_id"       => "$id1"
                , "reference_id"    => "$id2"
                , "file"            => "file"
                , "line"            => 2
                , "source"          => "a line"
                )
            );
        $this->assertEquals($expected, $res);
    }

    public function test_only_can_depend_on_2() {
        $rule = $this->only_a_classes_can_depend_on_globals();
        $id1 = $this->db->entity(Variable::CLASS_TYPE, "a_method", "file", 1, 2, "foo");
        $id2 = $this->db->reference(Variable::GLOBAL_TYPE, "glob", "file", 2);
        $this->db->relation("depend_on", $id1, $id2, "file", 2, "a line");
        $stmt = $rule->compile($this->db);

        $this->assertInstanceOf("\\Doctrine\\DBAL\\Driver\\Statement", $stmt);

        $res = $stmt->fetchAll();
        $expected = array
            ( array
                ( "entity_id"       => "$id1"
                , "reference_id"    => "$id2"
                , "file"            => "file"
                , "line"            => 2
                , "source"          => "a line"
                )
            );
        $this->assertEquals($expected, $res);
    }

    public function test_only_can_depend_on_3() {
        $rule = $this->only_a_classes_can_depend_on_globals();
        $id1 = $this->db->entity(Variable::METHOD_TYPE, "AClass", "file", 1, 2, "foo");
        $id2 = $this->db->reference(Variable::GLOBAL_TYPE, "glob", "file", 2);
        $this->db->relation("depend_on", $id1, $id2, "file", 2, "a line");
        $stmt = $rule->compile($this->db);

        $this->assertInstanceOf("\\Doctrine\\DBAL\\Driver\\Statement", $stmt);

        $res = $stmt->fetchAll();
        $expected = array
            ( array
                ( "entity_id"       => "$id1"
                , "reference_id"    => "$id2"
                , "file"            => "file"
                , "line"            => 2
                , "source"          => "a line"
                )
            );
        $this->assertEquals($expected, $res);
    }

    public function test_only_can_depend_on_4() {
        $rule = $this->only_a_classes_can_depend_on_globals();
        $id1 = $this->db->entity(Variable::CLASS_TYPE, "AClass", "file", 1, 2, "foo");
        $id2 = $this->db->reference(Variable::GLOBAL_TYPE, "glob", "file", 2);
        $this->db->relation("depend_on", $id1, $id2, "file", 2, "a line");
        $stmt = $rule->compile($this->db);

        $this->assertInstanceOf("\\Doctrine\\DBAL\\Driver\\Statement", $stmt);

        $res = $stmt->fetchAll();
        $this->assertEquals(array(), $res);
    }

    // AClasses must contain text "foo".

    public function a_classes_must_contain_text_foo() {
        return new Rules\Rule
            ( Rules\Rule::MODE_MUST
            , new Vars\WithName
                ( "AClass"
                , new Vars\Classes("AClasses")
                )
            , new Rules\ContainText()
            , array("foo")
            );
    }

    public function test_a_classes_must_contain_text_foo_1() {
        $rule = $this->a_classes_must_contain_text_foo();
        $id = $this->db->entity(Variable::CLASS_TYPE, "AClass", "file", 1, 2, "bar");
        $stmt = $rule->compile($this->db);

        $this->assertInstanceOf("\\Doctrine\\DBAL\\Driver\\Statement", $stmt);

        $res = $stmt->fetchAll();
        $expected = array
            ( array
                ( "entity_id"   => "$id"
                , "file"        => "file"
                , "source"      => "bar"
                )
            );
        $this->assertEquals($expected, $res);
    }

    public function test_a_classes_must_contain_text_foo_2() {
        $rule = $this->a_classes_must_contain_text_foo();
        $id = $this->db->entity(Variable::CLASS_TYPE, "BClass", "file", 1, 2, "foo");
        $stmt = $rule->compile($this->db);

        $this->assertInstanceOf("\\Doctrine\\DBAL\\Driver\\Statement", $stmt);

        $res = $stmt->fetchAll();
        $this->assertEquals(array(), $res);
    }

    public function test_a_classes_must_contain_text_foo_3() {
        $rule = $this->a_classes_must_contain_text_foo();
        $id = $this->db->entity(Variable::CLASS_TYPE, "BClass", "file", 1, 2, "bar");
        $stmt = $rule->compile($this->db);

        $this->assertInstanceOf("\\Doctrine\\DBAL\\Driver\\Statement", $stmt);

        $res = $stmt->fetchAll();
        $this->assertEquals(array(), $res);
    }

    public function test_a_classes_must_contain_text_foo_4() {
        $rule = $this->a_classes_must_contain_text_foo();
        $id = $this->db->entity(Variable::FUNCTION_TYPE, "AClass", "file", 1, 2, "bar");
        $stmt = $rule->compile($this->db);

        $this->assertInstanceOf("\\Doctrine\\DBAL\\Driver\\Statement", $stmt);

        $res = $stmt->fetchAll();
        $this->assertEquals(array(), $res);
    }

    // Only AClasses can contain text "foo".

    public function only_a_classes_can_contain_text_foo() {
        return new Rules\Rule
            ( Rules\Rule::MODE_ONLY_CAN
            , new Vars\WithName
                ( "AClass"
                , new Vars\Classes("AClasses")
                )
            , new Rules\ContainText()
            , array("foo")
            );
    }

    public function test_only_a_classes_can_contain_text_foo_1() {
        $rule = $this->only_a_classes_can_contain_text_foo();
        $id = $this->db->entity(Variable::CLASS_TYPE, "BClass", "file", 1, 2, "foo");
        $stmt = $rule->compile($this->db);

        $this->assertInstanceOf("\\Doctrine\\DBAL\\Driver\\Statement", $stmt);

        $res = $stmt->fetchAll();
        $expected = array
            ( array
                ( "entity_id"   => "$id"
                , "file"        => "file"
                , "source"      => "foo"
                )
            );
        $this->assertEquals($expected, $res);
    }

    public function test_only_a_classes_can_contain_text_foo_2() {
        $rule = $this->only_a_classes_can_contain_text_foo();
        $id = $this->db->entity(Variable::CLASS_TYPE, "AClass", "file", 1, 2, "foo");
        $stmt = $rule->compile($this->db);

        $this->assertInstanceOf("\\Doctrine\\DBAL\\Driver\\Statement", $stmt);

        $res = $stmt->fetchAll();
        $this->assertEquals(array(), $res);
    }

    public function test_only_a_classes_can_contain_text_foo_3() {
        $rule = $this->only_a_classes_can_contain_text_foo();
        $id = $this->db->entity(Variable::FUNCTION_TYPE, "AClass", "file", 1, 2, "foo");
        $stmt = $rule->compile($this->db);

        $this->assertInstanceOf("\\Doctrine\\DBAL\\Driver\\Statement", $stmt);

        $res = $stmt->fetchAll();
        $expected = array
            ( array
                ( "entity_id"   => "$id"
                , "file"        => "file"
                , "source"      => "foo"
                )
            );
        $this->assertEquals($expected, $res);
    }

    public function test_only_a_classes_can_contain_text_foo_4() {
        $rule = $this->only_a_classes_can_contain_text_foo();
        $id = $this->db->entity(Variable::CLASS_TYPE, "BClass", "file", 1, 2, "bar");
        $stmt = $rule->compile($this->db);

        $this->assertInstanceOf("\\Doctrine\\DBAL\\Driver\\Statement", $stmt);

        $res = $stmt->fetchAll();
        $this->assertEquals(array(), $res);
    }

    // AClasses must invoke functions.

    public function a_classes_must_invoke_functions() {
        return new Rules\Rule
            ( Rules\Rule::MODE_MUST
            , new Vars\WithName
                ( "AClass"
                , new Vars\Classes("AClasses")
                )
            , new Rules\Invoke()
            , array(new Vars\Functions("allFunctions"))
            );
    }

    public function test_a_classes_must_invoke_functions_1() {
        $rule = $this->a_classes_must_invoke_functions();
        $id1 = $this->db->entity(Variable::CLASS_TYPE, "AClass", "file", 1, 2, "foo");
        $id2 = $this->db->reference(Variable::FUNCTION_TYPE, "a_function", "file", 2);
        $stmt = $rule->compile($this->db);

        $this->assertInstanceOf("\\Doctrine\\DBAL\\Driver\\Statement", $stmt);

        $res = $stmt->fetchAll();
        $expected = array
            ( array
                ( "entity_id"   => "$id1"
                , "file"        => "file"
                , "line"        => "1"
                , "source"      => "foo"
                )
            );
        $this->assertEquals($expected, $res);
    }

    public function test_a_classes_must_invoke_functions_2() {
        $rule = $this->a_classes_must_invoke_functions();
        $id1 = $this->db->entity(Variable::CLASS_TYPE, "AClass", "file", 1, 2, "bar");
        $id2 = $this->db->reference(Variable::FUNCTION_TYPE, "a_function", "file", 2);
        $this->db->relation("invoke", $id1, $id2, "file", 2, "a line");
        $stmt = $rule->compile($this->db);

        $this->assertInstanceOf("\\Doctrine\\DBAL\\Driver\\Statement", $stmt);

        $res = $stmt->fetchAll();
        $this->assertEquals(array(), $res);
    }

    public function test_a_classes_must_invoke_functions_3() {
        $rule = $this->a_classes_must_invoke_functions();
        $id1 = $this->db->entity(Variable::CLASS_TYPE, "BClass", "file", 1, 2, "bar");
        $id2 = $this->db->reference(Variable::FUNCTION_TYPE, "a_function", "file", 2);
        $stmt = $rule->compile($this->db);

        $this->assertInstanceOf("\\Doctrine\\DBAL\\Driver\\Statement", $stmt);

        $res = $stmt->fetchAll();
        $this->assertEquals(array(), $res);
    }


    // Only AClasses can invoke functions.

    public function only_a_classes_can_invoke_functions() {
        return new Rules\Rule
            ( Rules\Rule::MODE_ONLY_CAN
            , new Vars\WithName
                ( "AClass"
                , new Vars\Classes("AClasses")
                )
            , new Rules\Invoke()
            , array(new Vars\Functions("allFunctions"))
            );
    }

    public function test_only_a_classes_can_invoke_function_1() {
        $rule = $this->only_a_classes_can_invoke_functions();
        $id1 = $this->db->entity(Variable::CLASS_TYPE, "BClass", "file", 1, 2, "foo");
        $id2 = $this->db->reference(Variable::FUNCTION_TYPE, "a_function", "file", 2);
        $this->db->relation("invoke", $id1, $id2, "file", 2, "a line");
        $stmt = $rule->compile($this->db);

        $this->assertInstanceOf("\\Doctrine\\DBAL\\Driver\\Statement", $stmt);

        $res = $stmt->fetchAll();
        $expected = array
            ( array
                ( "entity_id"       => "$id1"
                , "reference_id"    => "$id2"
                , "file"            => "file"
                , "line"            => 2
                , "source"          => "a line"
                )
            );
        $this->assertEquals($expected, $res);
    }

    public function test_only_a_classes_can_invoke_function_2() {
        $rule = $this->only_a_classes_can_invoke_functions();
        $id1 = $this->db->entity(Variable::CLASS_TYPE, "AClass", "file", 1, 2, "bar");
        $id2 = $this->db->reference(Variable::FUNCTION_TYPE, "a_function", "file", 2);
        $this->db->relation("invoke", $id1, $id2, "file", 2, "a line");
        $stmt = $rule->compile($this->db);

        $this->assertInstanceOf("\\Doctrine\\DBAL\\Driver\\Statement", $stmt);

        $res = $stmt->fetchAll();
        $this->assertEquals(array(), $res);
    }

    public function test_only_a_classes_can_invoke_function_3() {
        $rule = $this->only_a_classes_can_invoke_functions();
        $id1 = $this->db->entity(Variable::FUNCTION_TYPE, "AClass", "file", 1, 2, "bar");
        $id2 = $this->db->reference(Variable::FUNCTION_TYPE, "a_function", "file", 2);
        $this->db->relation("invoke", $id1, $id2, "file", 2, "a line");
        $stmt = $rule->compile($this->db);

        $this->assertInstanceOf("\\Doctrine\\DBAL\\Driver\\Statement", $stmt);

        $res = $stmt->fetchAll();
        $expected = array
            ( array
                ( "entity_id"       => "$id1"
                , "reference_id"    => "$id2"
                , "file"            => "file"
                , "line"            => 2
                , "source"          => "a line"
                )
            );
        $this->assertEquals($expected, $res);
    }
}
