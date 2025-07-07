<?php
declare(strict_types=1);

namespace Kickback\Common\Primitives;

final class Obj
{
    use \Kickback\Common\StaticClassTrait;

    /**
    * Uses the data in `$source` to populate the object/class `$dest`,
    * assuming that the keys in `$source` are the field (property)
    * names in `$dest`.
    *
    * This will only perform assignments when both `$dest` and `$source`
    * have a field/key with the same name:
    * * If `$source` doesn't have a key for a field in `$dest`, then no assignment will occur.
    * * If `$dest` doesn't have a field named the same thing as a key in `$source`, then no assignment will occur.
    *
    * Other miscellaneous properties of operation:
    * * As of PHP 9.0, "dynamic properties" will be forbidden.
    *     To get around it, have `$dest` inherit from `stdClass`.
    *     (Or, maybe, use #[AllowDynamicProperties].
    *     Not sure if it'll work with this much indirection, though.)
    * * Private and protected fields will be written to.
    * * As of this writing, readonly fields are ignored.
    *
    * As for type safety:
    *
    * When a field name and key match, an assignment will be attempted.
    * If the assignment throws an exception, then the exception will be caught
    * and discarded, and no further assignment will be attempted for that field.
    *
    * Field nullability is explicitly respected: if a field is non-nullable
    * and the corresponding array entry is `null`, then no assignment will be
    * performed.
    *
    * @param array<string,mixed> $source
    */
    public static function populateFromArray(object $dest, array $source) : object
    {
        $reflector = new \ReflectionClass($dest);
        $fields = $reflector->getProperties();
        foreach($fields as $field)
        {
            // TODO: Perform assignments if a field is readonly but also
            // hasn't been initialized yet? Supposedly, this is a valid
            // operation to perform:
            // https://www.php.net/manual/en/reflectionproperty.setvalue.php#128024
            // (For now, we don't need it.)
            if ( $field->isStatic() || $field->isReadOnly() ) {
                continue;
            }

            // Skip any fields that don't have data to obtain from $source.
            $name = $field->getName();
            if ( !array_key_exists($name, $source) ) {
                continue;
            }

            // Now we know we have something. Read it.
            $srcval = $source[$name];

            // Explicitly skip attempting to assign null values.
            // (As of this writing, it isn't clear if these would fail anyways.)
            $fieldType = $field->getType();
            if ( !is_null($fieldType) && !$fieldType->allowsNull() && is_null($srcval) ) {
                continue;
            }

            // Note that there is a comment in the `getValue` documentation
            // that suggests we need to set accessibility to true before
            // doing our thing:
            // https://www.php.net/manual/en/reflectionproperty.getvalue.php#98643
            //
            // However, as of PHP 8.1.0, it seems we don't need to worry about
            // it anymore:
            // https://www.php.net/manual/en/reflectionproperty.setaccessible.php
            //
            // As of this writing, we're already >8.1.0, and presumably don't
            // need to be backwards compatible, so let's get right into it.
            try
            {
                if ($field->isPublic())
                {
                    // Use native PHP assignment for public fields
                    $dest->$name = $srcval;
                    // I'm leaving the property.dynamicName error
                    // because this assignment is deprecated and will
                    // disappear in PHP 9.0. That said, I don't know
                    // of a better way.
                    // Note that there is another usage of this below,
                    // but I have silenced that one because there is no
                    // need for more than one to draw attention (later).
                }
                else
                {
                    // AI/ChatGPT seems to think that private and protected
                    // fields won't permite the above assignment, because
                    // we are outside of the class. It suggests that we can
                    // circumvent that problem by using a closure to _bind_
                    // to the class's scope.
                    //
                    // We also don't want to use `$field->setValue` or
                    // `$field->setValueRaw` because those functions may not
                    // respect type safety, and actually have very little to
                    // say about the subject. (This is, in a literal sense,
                    // "undefined behavior". Because it's not defined.)
                    //
                    $setter = function ($val) use ($name) {
                        // Within the closure, `$this` represents the `$dest`,
                        // but with private-accessible scoping.
                        // @phpstan-ignore variable.undefined property.dynamicName
                        $this->$name = $val;
                    };

                    $setter = \Closure::bind($setter, $dest, $reflector->getName());
                    $setter($srcval);
                }
            }
            catch(\Exception $e) {;}
        }
        return $dest;
    }
}
?>
