<?php

namespace DanielSiepmann\Tracking\Domain\Recordview;

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

use DanielSiepmann\Tracking\Domain\Model\RecordRule;
use DanielSiepmann\Tracking\Domain\Model\Recordview;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use TYPO3\CMS\Core\Routing\PageArguments;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;

class Factory
{
    public static function fromRequest(
        ServerRequestInterface $request,
        RecordRule $rule
    ): Recordview {
        // Need silent, as expression language doens't provide a way to check for array keys
        $recordUid = @(new ExpressionLanguage())->evaluate(
            $rule->getUidExpression(),
            ['request' => $request]
        );

        if (is_numeric($recordUid) === false) {
            throw new \UnexpectedValueException(
                sprintf(
                    'Could not determine record uid based on expression: "%1$s", got type "%2$s".',
                    $rule->getUidExpression(),
                    gettype($recordUid)
                ),
                1637846881
            );
        }

        return new Recordview(
            self::getRouting($request)->getPageId(),
            self::getLanguage($request),
            new \DateTimeImmutable(),
            (string) $request->getUri(),
            $request->getHeader('User-Agent')[0] ?? '',
            (int) $recordUid,
            $rule->getTableName()
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
