<?php

declare(strict_types=1);

namespace Shopsys\CodingStandards\Sniffs;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use SlevomatCodingStandard\Helpers\ClassHelper;

class ForbiddenPrivateSniff implements Sniff
{
    /**
     * @var string[]
     */
    public $forbiddenInClasses;

    /**
     * @return int[]
     */
    public function register(): array
    {
        return [T_PRIVATE];
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $file
     * @param int $position
     */
    public function process(File $file, $position): void
    {
        $fileClassName = $this->getFirstClassNameInFile($file);

        foreach ($this->forbiddenInClasses as $forbiddenClass) {
            if ($this->isClassForbidden($forbiddenClass, $fileClassName)) {
                $file->addError(
                    sprintf('Don\'t use "private" in %s. Found in "%s"', $forbiddenClass, $fileClassName),
                    $position,
                    self::class
                );
            }
        }
    }

    /**
     * We can not use Symplify\TokenRunner\Analyzer\SnifferAnalyzer\Naming::getClassName()
     * as it does not include namespace of declared class.
     *
     * @param \PHP_CodeSniffer\Files\File $file
     * @return string
     */
    private function getFirstClassNameInFile(File $file): string
    {
        $position = $file->findNext(T_CLASS, 0);

        return ClassHelper::getFullyQualifiedName($file, $position);
    }

    /**
     * @param string $forbiddenClass
     * @param string $fileClassName
     * @return bool
     */
    private function isClassForbidden(string $forbiddenClass, string $fileClassName): bool
    {
        return strpos($fileClassName, $forbiddenClass) !== false;
    }
}
