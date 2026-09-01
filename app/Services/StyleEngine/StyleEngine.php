<?php

namespace App\Services\StyleEngine;

use App\Enums\EnforcementMode;
use App\Models\DetectedElement;
use App\Models\StyleProfile;
use App\Models\StyleViolation;
use App\Services\StyleEngine\Checks\AlignmentCheck;
use App\Services\StyleEngine\Checks\AllCapsCheck;
use App\Services\StyleEngine\Checks\BoldCheck;
use App\Services\StyleEngine\Checks\BordersCheck;
use App\Services\StyleEngine\Checks\FontColorCheck;
use App\Services\StyleEngine\Checks\FontFamilyCheck;
use App\Services\StyleEngine\Checks\FontSizeCheck;
use App\Services\StyleEngine\Checks\IndentationCheck;
use App\Services\StyleEngine\Checks\ItalicCheck;
use App\Services\StyleEngine\Checks\LineSpacingCheck;
use App\Services\StyleEngine\Checks\NumberingCheck;
use App\Services\StyleEngine\Checks\ParagraphStyleCheck;
use App\Services\StyleEngine\Checks\ShadingCheck;
use App\Services\StyleEngine\Checks\SmallCapsCheck;
use App\Services\StyleEngine\Checks\SpacingCheck;
use App\Services\StyleEngine\Checks\UnderlineCheck;

class StyleEngine
{
    /** @var StyleCheckInterface[] */
    private array $checks = [];

    public function __construct()
    {
        $this->registerDefaults();
    }

    public function registerCheck(StyleCheckInterface $check): void
    {
        $this->checks[$check->getCheckType()] = $check;
    }

    public function getEnabledChecks(array $enabledCheckTypes): array
    {
        if (empty($enabledCheckTypes)) {
            return $this->checks;
        }

        return array_filter(
            $this->checks,
            fn (StyleCheckInterface $check) => in_array($check->getCheckType(), $enabledCheckTypes)
        );
    }

    /**
     * @param  DetectedElement[]  $elements
     * @return StyleViolation[]
     */
    public function analyze(array $elements, StyleProfile $profile, array $enabledCheckTypes = [], ?EnforcementMode $enforcementMode = null): array
    {
        $violations = [];
        $checks = $this->getEnabledChecks($enabledCheckTypes);
        $rules = $profile->rules;

        foreach ($elements as $element) {
            $elementRules = $this->getRulesForElement($element, $rules);

            foreach ($checks as $check) {
                foreach ($elementRules as $rule) {
                    $violation = $check->check($element, $rule);
                    if ($violation !== null) {
                        $violation->detected_element_id = $element->id;

                        if ($enforcementMode !== null) {
                            $violation->auto_fix = $enforcementMode === EnforcementMode::Strict;
                        }

                        $violations[] = $violation;
                    }
                }
            }
        }

        return $violations;
    }

    private function getRulesForElement(DetectedElement $element, array $rules): array
    {
        $elementRules = [];

        if ($element->type === 'heading' && isset($element->heading_level)) {
            $levelKey = 'heading_'.$element->heading_level;
            if (isset($rules[$levelKey])) {
                $elementRules[] = $rules[$levelKey];
            }
        }

        if ($element->type === 'paragraph' && isset($rules['body'])) {
            $elementRules[] = $rules['body'];
        }

        if ($element->type === 'caption' && isset($rules['captions'])) {
            $elementRules[] = $rules['captions'];
        }

        if ($element->type === 'source' && isset($rules['sources'])) {
            $elementRules[] = $rules['sources'];
        }

        return $elementRules;
    }

    private function registerDefaults(): void
    {
        $this->registerCheck(new FontFamilyCheck);
        $this->registerCheck(new FontSizeCheck);
        $this->registerCheck(new FontColorCheck);
        $this->registerCheck(new BoldCheck);
        $this->registerCheck(new ItalicCheck);
        $this->registerCheck(new UnderlineCheck);
        $this->registerCheck(new AlignmentCheck);
        $this->registerCheck(new AllCapsCheck);
        $this->registerCheck(new SmallCapsCheck);
        $this->registerCheck(new IndentationCheck);
        $this->registerCheck(new LineSpacingCheck);
        $this->registerCheck(new SpacingCheck);
        $this->registerCheck(new NumberingCheck);
        $this->registerCheck(new BordersCheck);
        $this->registerCheck(new ShadingCheck);
        $this->registerCheck(new ParagraphStyleCheck);
    }
}
