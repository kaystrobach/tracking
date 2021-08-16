<?php

declare(strict_types=1);

/*
 * Copyright (C) 2021 Daniel Siepmann <coding@daniel-siepmann.de>
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

namespace DanielSiepmann\Tracking\Domain\Extractors;

use DanielSiepmann\Tracking\Domain\Extractors\Bots\CustomBotParser;
use DanielSiepmann\Tracking\Domain\Model\Pageview;
use DanielSiepmann\Tracking\Domain\Model\Recordview;
use DeviceDetector\DeviceDetector;

class Bots implements PageviewExtractor, RecordviewExtractor
{
    /**
     * @var DeviceDetector
     */
    private $deviceDetector;

    public function __construct(
        CustomBotParser $customBotParser
    ) {
        $this->deviceDetector = new DeviceDetector();
        $this->deviceDetector->addBotParser($customBotParser);
    }

    public function extractTagFromPageview(Pageview $pageview): array
    {
        return $this->getTagsForUserAgent($pageview->getUserAgent());
    }

    public function extractTagFromRecordview(Recordview $recordview): array
    {
        return $this->getTagsForUserAgent($recordview->getUserAgent());
    }

    /**
     * @return Tag[]
     */
    private function getTagsForUserAgent(string $userAgent): array
    {
        $botNameTag = new Tag('bot_name', $this->getBotName($userAgent));

        if ($botNameTag->getValue() !== '') {
            return [
                new Tag('bot', 'yes'),
                $botNameTag,
            ];
        }
        return [new Tag('bot', 'no')];
    }

    private function getBotName(string $userAgent): string
    {
        $this->deviceDetector->setUserAgent($userAgent);
        $this->deviceDetector->parse();

        if ($this->deviceDetector->isBot() === false) {
            return '';
        }

        $bot = $this->deviceDetector->getBot();
        if (is_array($bot) === false) {
            return '';
        }

        $name = $bot['name'] ?? '';
        if ($name === 'Generic Bot') {
            $name = $this->additionalBotDetection($userAgent);
        }

        return $name;
    }

    /**
     * We have some cases which don't work with matomo, don't know the reason.
     * So we add them here.
     */
    private function additionalBotDetection(string $userAgent): string
    {
        $searchWords = [
            'MauiBot' => 'Maui Bot',
            'www.fim.uni-passau.de' => 'hgfAlphaXCrawl - Uni Passau',
        ];

        foreach ($searchWords as $searchWord => $name) {
            if (mb_stripos($userAgent, $searchWord) !== false) {
                return $name;
            }
        }

        return '';
    }
}
