<?php

namespace DanielSiepmann\Tracking\Domain\Pageview;

/*
 * Copyright (C) 2020 Daniel Siepmann <coding@daniel-siepmann.de>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA
 * 02110-1301, USA.
 */

use DanielSiepmann\Tracking\Domain\Model\Pageview;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Routing\PageArguments;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Site\SiteFinder;

class Factory
{
    /**
     * @var SiteFinder
     */
    private $siteFinder;

    public function __construct(SiteFinder $siteFinder)
    {
        $this->siteFinder = $siteFinder;
    }

    public static function fromRequest(ServerRequestInterface $request): Pageview
    {
        return new Pageview(
            self::getRouting($request)->getPageId(),
            self::getLanguage($request),
            new \DateTimeImmutable(),
            (int) self::getRouting($request)->getPageType(),
            (string) $request->getUri(),
            $request->getHeader('User-Agent')[0] ?? ''
        );
    }

    public function fromDbRow(array $dbRow): Pageview
    {
        return new Pageview(
            $dbRow['pid'],
            $this->siteFinder->getSiteByPageId($dbRow['pid'])->getLanguageById($dbRow['sys_language_uid']),
            new \DateTimeImmutable('@' . $dbRow['crdate']),
            $dbRow['type'],
            $dbRow['url'],
            $dbRow['user_agent'],
            $dbRow['uid']
        );
    }

    private static function getLanguage(ServerRequestInterface $request): SiteLanguage
    {
        $language = $request->getAttribute('language');

        if (!$language instanceof SiteLanguage) {
            throw new \UnexpectedValueException('Could not fetch SiteLanguage from request attributes.', 1637847002);
        }

        return $language;
    }

    private static function getRouting(ServerRequestInterface $request): PageArguments
    {
        $routing = $request->getAttribute('routing');

        if (!$routing instanceof PageArguments) {
            throw new \UnexpectedValueException('Could not fetch PageArguments from request attributes.', 1637847002);
        }

        return $routing;
    }
}
