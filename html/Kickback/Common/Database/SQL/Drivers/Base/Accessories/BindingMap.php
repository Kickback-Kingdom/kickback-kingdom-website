<?php
declare(strict_types=1);

namespace Kickback\Common\Database\SQL\Drivers\Base\Accessories;

use ReflectionClass;
use ReflectionProperty;

use Kickback\Common\Database\SQL\Accessories\SQL_BindingMap;

// TODO: implement this stuff

final class BindingMap implements SQL_BindingMap
{
    private  ReflectionClass  $class_reflector;

    /** @var  ReflectionProperty[] */
    private  array            $bound_field_reflectors;

    /** @var  array<int|string, ReflectionProperty> */
    private  array            $binding_list;

    /**
    * @param class-string $fqn_of_class_to_bind
    */
    public function __construct(string  $fqn_of_class_to_bind)
    {
        $this->class_reflector = new ReflectionClass($fqn_of_class_to_bind);
        $this->bound_field_reflectors = [];
        $this->binding_list = [];
    }
/*
    private function collate_bindings() : void
    {
        // TODO: Ensure that $binding_list is empty after this is called.
    }
*/
    private function insert_binding(int|string $column_number_or_name, ReflectionProperty $field) : SQL_BindingMap
    {
        // TODO: Type-safety? Accessors? (How will columns get converted if there's a natural type conversion?)
        return $this;
    }

    public function bool     (int|string $cnon, string $field_name) : SQL_BindingMap { return $this->insert_binding($cnon, $this->class_reflector->getProperty($field_name)); }
/*
    public function DateTime (int|string $cnon, string $field_name) : SQL_BindingMap;
    public function float    (int|string $cnon, string $field_name) : SQL_BindingMap;
    public function int      (int|string $cnon, string $field_name) : SQL_BindingMap;
    public function str      (int|string $cnon, string $field_name) : SQL_BindingMap;
    public function string   (int|string $cnon, string $field_name) : SQL_BindingMap;
    public function nbool    (int|string $cnon, string $field_name) : SQL_BindingMap;
    public function nDateTime(int|string $cnon, string $field_name) : SQL_BindingMap;
    public function nfloat   (int|string $cnon, string $field_name) : SQL_BindingMap;
    public function nint     (int|string $cnon, string $field_name) : SQL_BindingMap;
    public function nstr     (int|string $cnon, string $field_name) : SQL_BindingMap;
    public function nstring  (int|string $cnon, string $field_name) : SQL_BindingMap;
*/
    public function count() : int {
        return count($this->binding_list) + count($this->bound_field_reflectors);
    }
}

?>
