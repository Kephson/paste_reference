<?php

declare(strict_types=1);

namespace EHAERER\PasteReference\Tests\Unit;

/***************************************************************
 *  Copyright notice
 *  (c) 2023 Ephraim Härer <mail@ephra.im>
 *  (c) 2013 Jo Hasenau <info@cybercraft.de>
 *  All rights reserved
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class DummyTest extends FunctionalTestCase
{
    public function setUp(): void
    {
        $this->testExtensionsToLoad[] = 'ehaerer/paste-reference';
        parent::setUp();
    }

    #[Test]
    public function extensionLoaded(): void
    {
        self::assertTrue(ExtensionManagementUtility::isLoaded('paste_reference'));
    }
}
