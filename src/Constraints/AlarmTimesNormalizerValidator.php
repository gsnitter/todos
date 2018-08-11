<?php

namespace APPointer\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use APPointer\Parser\AlarmTimesParser;

class AlarmTimesNormalizerValidator extends ConstraintValidator
{
    public function __construct(AlarmTimesParser $parser)
    {
        $this->parser = $parser;
    }

    public function validate($object, Constraint $constraint)
    {
        $getter = 'get' . ucfirst($constraint->path);
        $setter = 'setNormalized' . ucfirst($constraint->path);

        if (!method_exists($object, $getter)) {
            $this->context->buildViolation("No getter function {$getter} found.")
                ->atPath($constraint->path)
                ->addViolation();
        }

        $value = $object->$getter();
        if (!$value) {
            return;
        }
        $normalizedDateStringGetter = $constraint->normalizedDateStringGetter;
        $normalizedDateString = $object->$normalizedDateStringGetter();

        if (!preg_match('@^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$@', $normalizedDateString)) {
            $text = "Cannot parse alarm times, since date string {$normalizedDateString} is no date string.";

            $this->context->buildViolation($text)
                ->atPath($constraint->path)
                ->addViolation();
            return;
        }

        try {
            $this->parser->setNormalizedDateString($normalizedDateString);
            $normalizedValue = $this->parser->normalize($value);
        } catch (\Exception $e) {
            $normalizedValue = '';
        }

        if (!$normalizedValue) {
            $text = 'The alarm times ' . print_r($value, true) . ' cannot be parsed.';
            $this->context->buildViolation($text)
                ->atPath($constraint->path)
                ->addViolation();
        } else {
            if (!method_exists($object, $setter)) {
                $this->context->buildViolation("No setter function {$setter} found.")
                    ->atPath($contraint->path)
                    ->addViolation();
            }
            $object->$setter($normalizedValue);
        }
    }
}
