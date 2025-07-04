<?php
declare(strict_types=1);

namespace Kickback\Common\Database\SQL\Accessories;

// TODO: Does this even need to be polymorphic?
// We might be able to avoid dynamic dispatch for this
// if the binding-map logic is portable to every SQL implementation.
// It'd be nice to know how PHP's `mysqli_stmt::bind_result` function
// is implemented. Does it do anything special that we can't do?

interface SQL_BindingMap extends \Countable
{
    public function bool     (int|string $column_number_or_name, string $field_name) : SQL_BindingMap;
/*
    public function DateTime (int|string $column_number_or_name, string $field_name) : SQL_BindingMap;
    public function float    (int|string $column_number_or_name, string $field_name) : SQL_BindingMap;
    public function int      (int|string $column_number_or_name, string $field_name) : SQL_BindingMap;
    public function str      (int|string $column_number_or_name, string $field_name) : SQL_BindingMap;
    public function string   (int|string $column_number_or_name, string $field_name) : SQL_BindingMap;
    public function nbool    (int|string $column_number_or_name, string $field_name) : SQL_BindingMap;
    public function nDateTime(int|string $column_number_or_name, string $field_name) : SQL_BindingMap;
    public function nfloat   (int|string $column_number_or_name, string $field_name) : SQL_BindingMap;
    public function nint     (int|string $column_number_or_name, string $field_name) : SQL_BindingMap;
    public function nstr     (int|string $column_number_or_name, string $field_name) : SQL_BindingMap;
    public function nstring  (int|string $column_number_or_name, string $field_name) : SQL_BindingMap;
*/
    public function count() : int;
}

?>
