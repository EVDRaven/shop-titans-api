<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class BlueprintExcelReader
{
    private const COLUMN_NAME = 'A';

    private const COLUMN_TYPE = 'B';

    private const COLUMN_UNLOCK_PREREQUISITE = 'C';

    private const COLUMN_RESEARCH_SCROLLS = 'D';

    private const COLUMN_ANTIQUE_TOKENS = 'E';

    private const COLUMN_TIER = 'F';

    private const COLUMN_GOLD_VALUE = 'G';

    private const COLUMN_CRAFT_TIME_SECONDS = 'H';

    private const COLUMN_CRAFT_TIME_FORMATTED = 'I';

    private const COLUMN_VALUE_PER_SECOND = 'J';

    private const COLUMN_MERCHANT_XP = 'K';

    private const COLUMN_MERCHANT_XP_PER_SECOND = 'L';

    private const COLUMN_WORKER_XP = 'M';

    private const COLUMN_FUSION_XP = 'N';

    private const COLUMN_FAVOR = 'O';

    private const COLUMN_AIRSHIP_POWER = 'P';

    private const COLUMN_WORKER_1 = 'R';

    private const COLUMN_WORKER_1_LEVEL = 'S';

    private const COLUMN_WORKER_2 = 'T';

    private const COLUMN_WORKER_2_LEVEL = 'U';

    private const COLUMN_WORKER_3 = 'V';

    private const COLUMN_WORKER_3_LEVEL = 'W';

    private const RESOURCE_COLUMNS = [
        'Y' => 'Iron',
        'Z' => 'Wood',
        'AA' => 'Leather',
        'AB' => 'Herbs',
        'AC' => 'Steel',
        'AD' => 'Ironwood',
        'AE' => 'Fabric',
        'AF' => 'Oils',
        'AG' => 'Mana',
        'AH' => 'Gems',
        'AI' => 'Essence',
        'AJ' => 'Stardust',
    ];

    private const COLUMN_ATK = 'AS';

    private const COLUMN_DEF = 'AT';

    private const COLUMN_HP = 'AU';

    private const COLUMN_EVA = 'AV';

    private const COLUMN_CRIT = 'AW';

    private const COLUMN_MANA_SHIELD = 'AX';

    private const COLUMN_ELEMENTAL_AFFINITY = 'AZ';

    private const COLUMN_SPIRIT_AFFINITY = 'BA';

    private const COLUMN_BUILT_IN_ELEMENT = 'BB';

    private const COLUMN_BUILT_IN_SPIRIT = 'BC';

    private const CRAFTING_UPGRADE_COLUMNS = [
        ['name' => 'BE', 'crafts' => 'BF'],
        ['name' => 'BG', 'crafts' => 'BH'],
        ['name' => 'BI', 'crafts' => 'BJ'],
        ['name' => 'BK', 'crafts' => 'BL'],
        ['name' => 'BM', 'crafts' => 'BN'],
    ];

    private const STARFORGED_COLUMNS = [
        ['name' => 'BP', 'crafts' => 'BQ'],
        ['name' => 'BR', 'crafts' => 'BS'],
        ['name' => 'BT', 'crafts' => 'BU'],
        ['name' => 'BV', 'crafts' => 'BW'],
        ['name' => 'BX', 'crafts' => 'BY'],
    ];

    private const ASCENSION_COLUMNS = [
        ['name' => 'CA', 'shards' => 'CB'],
        ['name' => 'CC', 'shards' => 'CD'],
        ['name' => 'CE', 'shards' => 'CF'],
    ];

    private const TRANSCENDENCE_COLUMNS = [
        ['name' => 'CG', 'seals' => 'CH'],
        ['name' => 'CI', 'seals' => 'CJ'],
        ['name' => 'CK', 'seals' => 'CL'],
    ];

    private const COLUMN_DISCOUNT_ENERGY = 'CN';

    private const COLUMN_SURCHARGE_ENERGY = 'CO';

    private const COLUMN_SUGGEST_ENERGY = 'CP';

    private const COLUMN_SPEED_UP_ENERGY = 'CQ';

    private const COLUMN_AVAILABLE_AS_ANTIQUE_AT = 'CS';

    private const COLUMN_COMPONENT_1 = 'AL';

    private const COLUMN_COMPONENT_1_QUALITY = 'AM';

    private const COLUMN_COMPONENT_1_AMOUNT = 'AN';

    private const COLUMN_COMPONENT_2 = 'AO';

    private const COLUMN_COMPONENT_2_QUALITY = 'AP';

    private const COLUMN_COMPONENT_2_AMOUNT = 'AQ';

    private const QUEST_COMPONENTS_SHEET = 'Quest Components';

    private const FULL_MOON_FUSIONS_SHEET = 'Full Moon Fusions';

    private const ENCHANTMENTS_SHEET = 'Enchantments';

    public const ENCHANTMENT_TYPE = 'Enchantment';

    public function read(string $filePath): Collection
    {
        $reader = IOFactory::createReader('Xlsx');
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($filePath);
        $worksheet = $spreadsheet->getSheetByName('Blueprints');

        $rows = collect();
        $highestRow = $worksheet->getHighestRow();

        for ($row = 2; $row <= $highestRow; $row++) {
            $name = $this->getString($worksheet, $row, self::COLUMN_NAME);
            if ($name === null) {
                continue;
            }

            $type = $this->getString($worksheet, $row, self::COLUMN_TYPE);
            if ($type === self::ENCHANTMENT_TYPE) {
                continue;
            }

            $rows->push($this->parseRow($worksheet, $row));
        }

        $spreadsheet->disconnectWorksheets();

        return $rows;
    }

    public function readQuestComponents(string $filePath): Collection
    {
        $reader = IOFactory::createReader('Xlsx');
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($filePath);
        $worksheet = $spreadsheet->getSheetByName(self::QUEST_COMPONENTS_SHEET);

        $rows = collect();
        $highestRow = $worksheet->getHighestRow();

        for ($row = 2; $row <= $highestRow; $row++) {
            $name = $this->getString($worksheet, $row, 'A');
            if ($name === null) {
                continue;
            }

            $rows->put($name, [
                'name' => $name,
                'type' => $this->getString($worksheet, $row, 'B'),
                'tier' => $this->getInt($worksheet, $row, 'C'),
                'gold_value' => $this->getInt($worksheet, $row, 'D'),
                'quest_area' => $this->getString($worksheet, $row, 'E'),
            ]);
        }

        $spreadsheet->disconnectWorksheets();

        return $rows;
    }

    public function readSigils(string $filePath): Collection
    {
        $reader = IOFactory::createReader('Xlsx');
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($filePath);
        $worksheet = $spreadsheet->getSheetByName(self::FULL_MOON_FUSIONS_SHEET);

        $rows = collect();
        $highestRow = $worksheet->getHighestRow();

        for ($row = 2; $row <= $highestRow; $row++) {
            $name = $this->getString($worksheet, $row, 'A');
            $type = $this->getString($worksheet, $row, 'B');

            if ($name === null || $type !== 'Component' || ! str_starts_with($name, 'Sigil of')) {
                continue;
            }

            $rows->put($name, [
                'name' => $name,
                'worker_level_required' => $this->getInt($worksheet, $row, 'C'),
                'fuse_time_seconds' => $this->getInt($worksheet, $row, 'E'),
                'worker_xp' => $this->getInt($worksheet, $row, 'G'),
                'tier' => $this->getInt($worksheet, $row, 'J'),
            ]);
        }

        $spreadsheet->disconnectWorksheets();

        return $rows;
    }

    public function readEnchantmentNames(string $filePath): Collection
    {
        $reader = IOFactory::createReader('Xlsx');
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($filePath);
        $worksheet = $spreadsheet->getSheetByName('Blueprints');

        $names = collect();
        $highestRow = $worksheet->getHighestRow();

        for ($row = 2; $row <= $highestRow; $row++) {
            $name = $this->getString($worksheet, $row, self::COLUMN_NAME);
            $type = $this->getString($worksheet, $row, self::COLUMN_TYPE);

            if ($name !== null && $type === self::ENCHANTMENT_TYPE) {
                $names->put($name, $name);
            }
        }

        $spreadsheet->disconnectWorksheets();

        return $names;
    }

    public function readBlueprintNames(string $filePath): Collection
    {
        $reader = IOFactory::createReader('Xlsx');
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($filePath);
        $worksheet = $spreadsheet->getSheetByName('Blueprints');

        $names = collect();
        $highestRow = $worksheet->getHighestRow();

        for ($row = 2; $row <= $highestRow; $row++) {
            $name = $this->getString($worksheet, $row, self::COLUMN_NAME);
            $type = $this->getString($worksheet, $row, self::COLUMN_TYPE);

            if ($name !== null && $type !== self::ENCHANTMENT_TYPE) {
                $names->put($name, $name);
            }
        }

        $spreadsheet->disconnectWorksheets();

        return $names;
    }

    public function readComponentNames(string $filePath): Collection
    {
        $reader = IOFactory::createReader('Xlsx');
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($filePath);
        $worksheet = $spreadsheet->getSheetByName('Blueprints');

        $names = collect();
        $highestRow = $worksheet->getHighestRow();

        for ($row = 2; $row <= $highestRow; $row++) {
            $type = $this->getString($worksheet, $row, self::COLUMN_TYPE);
            if ($type === self::ENCHANTMENT_TYPE) {
                continue;
            }

            foreach ([self::COLUMN_COMPONENT_1, self::COLUMN_COMPONENT_2] as $column) {
                $name = $this->getString($worksheet, $row, $column);
                if ($name !== null) {
                    $names->put($name, $name);
                }
            }
        }

        $spreadsheet->disconnectWorksheets();

        return $names;
    }

    public function readCraftingMaterialData(string $filePath): array
    {
        $reader = IOFactory::createReader('Xlsx');
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($filePath);

        $blueprintNames = collect();
        $enchantmentNames = collect();
        $componentNames = collect();

        $blueprintsSheet = $spreadsheet->getSheetByName('Blueprints');
        $highestRow = $blueprintsSheet->getHighestRow();

        for ($row = 2; $row <= $highestRow; $row++) {
            $name = $this->getString($blueprintsSheet, $row, self::COLUMN_NAME);
            $type = $this->getString($blueprintsSheet, $row, self::COLUMN_TYPE);

            if ($name === null) {
                continue;
            }

            if ($type === self::ENCHANTMENT_TYPE) {
                $enchantmentNames->put($name, $name);

                continue;
            }

            $blueprintNames->put($name, $name);

            foreach ([self::COLUMN_COMPONENT_1, self::COLUMN_COMPONENT_2] as $column) {
                $componentName = $this->getString($blueprintsSheet, $row, $column);
                if ($componentName !== null) {
                    $componentNames->put($componentName, $componentName);
                }
            }
        }

        $questComponents = collect();
        $qcSheet = $spreadsheet->getSheetByName(self::QUEST_COMPONENTS_SHEET);
        $qcHighestRow = $qcSheet->getHighestRow();

        for ($row = 2; $row <= $qcHighestRow; $row++) {
            $name = $this->getString($qcSheet, $row, 'A');
            if ($name === null) {
                continue;
            }

            $questComponents->put($name, [
                'name' => $name,
                'type' => $this->getString($qcSheet, $row, 'B'),
                'tier' => $this->getInt($qcSheet, $row, 'C'),
                'gold_value' => $this->getInt($qcSheet, $row, 'D'),
                'quest_area' => $this->getString($qcSheet, $row, 'E'),
            ]);
        }

        $sigils = collect();
        $fmfSheet = $spreadsheet->getSheetByName(self::FULL_MOON_FUSIONS_SHEET);
        $fmfHighestRow = $fmfSheet->getHighestRow();

        for ($row = 2; $row <= $fmfHighestRow; $row++) {
            $name = $this->getString($fmfSheet, $row, 'A');
            $type = $this->getString($fmfSheet, $row, 'B');

            if ($name === null || $type !== 'Component' || ! str_starts_with($name, 'Sigil of')) {
                continue;
            }

            $sigils->put($name, [
                'name' => $name,
                'worker_level_required' => $this->getInt($fmfSheet, $row, 'C'),
                'fuse_time_seconds' => $this->getInt($fmfSheet, $row, 'E'),
                'worker_xp' => $this->getInt($fmfSheet, $row, 'G'),
                'tier' => $this->getInt($fmfSheet, $row, 'J'),
            ]);
        }

        $spreadsheet->disconnectWorksheets();

        return [
            'componentNames' => $componentNames,
            'blueprintNames' => $blueprintNames,
            'questComponents' => $questComponents,
            'sigils' => $sigils,
            'enchantmentNames' => $enchantmentNames,
        ];
    }

    public function readElementalAffinityTokens(string $filePath): Collection
    {
        return $this->distinctBlueprintColumnTokens($filePath, self::COLUMN_ELEMENTAL_AFFINITY, true);
    }

    public function readSpiritAffinityNames(string $filePath): Collection
    {
        return $this->distinctBlueprintColumnTokens($filePath, self::COLUMN_SPIRIT_AFFINITY);
    }

    public function readBuiltInElementNames(string $filePath): Collection
    {
        return $this->distinctBlueprintColumnTokens($filePath, self::COLUMN_BUILT_IN_ELEMENT);
    }

    public function readBuiltInSpiritNames(string $filePath): Collection
    {
        return $this->distinctBlueprintColumnTokens($filePath, self::COLUMN_BUILT_IN_SPIRIT);
    }

    public function readEnchantmentCatalog(string $filePath): Collection
    {
        $reader = IOFactory::createReader('Xlsx');
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($filePath);
        $worksheet = $spreadsheet->getSheetByName(self::ENCHANTMENTS_SHEET);

        $entries = collect();
        $highestRow = $worksheet->getHighestRow();

        for ($row = 2; $row <= $highestRow; $row++) {
            $name = $this->getString($worksheet, $row, 'A');
            $type = $this->getString($worksheet, $row, 'B');

            if ($name === null || $type === null) {
                continue;
            }

            $entries->put($name, ['name' => $name, 'type' => $type]);
        }

        $spreadsheet->disconnectWorksheets();

        return $entries;
    }

    private function distinctBlueprintColumnTokens(string $filePath, string $column, bool $splitCommas = false): Collection
    {
        $reader = IOFactory::createReader('Xlsx');
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($filePath);
        $worksheet = $spreadsheet->getSheetByName('Blueprints');

        $tokens = collect();
        $highestRow = $worksheet->getHighestRow();

        for ($row = 2; $row <= $highestRow; $row++) {
            $name = $this->getString($worksheet, $row, self::COLUMN_NAME);

            if ($name === null || $this->getString($worksheet, $row, self::COLUMN_TYPE) === self::ENCHANTMENT_TYPE) {
                continue;
            }

            $value = $this->getString($worksheet, $row, $column);

            if ($value === null) {
                continue;
            }

            $parts = $splitCommas ? $this->parseElementalAffinities($value) : [$value];

            foreach ($parts as $part) {
                $tokens->put($part, $part);
            }
        }

        $spreadsheet->disconnectWorksheets();

        return $tokens;
    }

    private function parseRow(Worksheet $worksheet, int $row): BlueprintRowData
    {
        $elementalAffinityRaw = $this->getString($worksheet, $row, self::COLUMN_ELEMENTAL_AFFINITY);

        return new BlueprintRowData(
            name: $this->getString($worksheet, $row, self::COLUMN_NAME) ?? '',
            categoryName: $this->getString($worksheet, $row, self::COLUMN_TYPE),
            unlockPrerequisite: $this->getString($worksheet, $row, self::COLUMN_UNLOCK_PREREQUISITE),
            researchScrolls: $this->getInt($worksheet, $row, self::COLUMN_RESEARCH_SCROLLS),
            antiqueTokens: $this->getInt($worksheet, $row, self::COLUMN_ANTIQUE_TOKENS),
            tier: $this->getInt($worksheet, $row, self::COLUMN_TIER) ?? 0,
            goldValue: $this->getInt($worksheet, $row, self::COLUMN_GOLD_VALUE) ?? 0,
            craftTimeSeconds: $this->getInt($worksheet, $row, self::COLUMN_CRAFT_TIME_SECONDS) ?? 0,
            craftTimeFormatted: $this->getString($worksheet, $row, self::COLUMN_CRAFT_TIME_FORMATTED),
            valuePerSecond: $this->getFloat($worksheet, $row, self::COLUMN_VALUE_PER_SECOND),
            merchantXp: $this->getInt($worksheet, $row, self::COLUMN_MERCHANT_XP) ?? 0,
            merchantXpPerSecond: $this->getFloat($worksheet, $row, self::COLUMN_MERCHANT_XP_PER_SECOND),
            workerXp: $this->getInt($worksheet, $row, self::COLUMN_WORKER_XP) ?? 0,
            fusionXp: $this->getInt($worksheet, $row, self::COLUMN_FUSION_XP) ?? 0,
            favor: $this->getInt($worksheet, $row, self::COLUMN_FAVOR) ?? 0,
            airshipPower: $this->getInt($worksheet, $row, self::COLUMN_AIRSHIP_POWER) ?? 0,
            atk: $this->getInt($worksheet, $row, self::COLUMN_ATK),
            def: $this->getInt($worksheet, $row, self::COLUMN_DEF),
            hp: $this->getInt($worksheet, $row, self::COLUMN_HP),
            eva: $this->getInt($worksheet, $row, self::COLUMN_EVA),
            crit: $this->getInt($worksheet, $row, self::COLUMN_CRIT),
            manaShield: $this->getInt($worksheet, $row, self::COLUMN_MANA_SHIELD),
            discountEnergy: $this->getInt($worksheet, $row, self::COLUMN_DISCOUNT_ENERGY),
            surchargeEnergy: $this->getInt($worksheet, $row, self::COLUMN_SURCHARGE_ENERGY),
            suggestEnergy: $this->getInt($worksheet, $row, self::COLUMN_SUGGEST_ENERGY),
            speedUpEnergy: $this->getInt($worksheet, $row, self::COLUMN_SPEED_UP_ENERGY),
            availableAsAntiqueAt: $this->getDateString($worksheet, $row, self::COLUMN_AVAILABLE_AS_ANTIQUE_AT),
            workers: $this->parseWorkers($worksheet, $row),
            resources: $this->parseResources($worksheet, $row),
            craftingUpgrades: $this->parseCraftingUpgrades($worksheet, $row),
            starforgedMilestones: $this->parseStarforged($worksheet, $row),
            ascensionUpgrades: $this->parseAscension($worksheet, $row),
            transcendenceUpgrades: $this->parseTranscendence($worksheet, $row),
            components: $this->parseComponents($worksheet, $row),
            elementalAffinityRaw: $elementalAffinityRaw,
            elementalAffinities: $this->parseElementalAffinities($elementalAffinityRaw),
            spiritAffinityName: $this->getString($worksheet, $row, self::COLUMN_SPIRIT_AFFINITY),
            builtInElementName: $this->getString($worksheet, $row, self::COLUMN_BUILT_IN_ELEMENT),
            builtInSpiritName: $this->getString($worksheet, $row, self::COLUMN_BUILT_IN_SPIRIT),
        );
    }

    /**
     * Divide el valor de Elemental Affinity preservando el orden original de la celda.
     * El primer elemento mantiene la prioridad 1 y el segundo la prioridad 2.
     *
     * @return array<int, string>
     */
    private function parseElementalAffinities(?string $raw): array
    {
        if ($raw === null) {
            return [];
        }

        $tokens = array_map('trim', explode(',', $raw));

        return array_values(array_filter($tokens, fn (string $token) => $token !== ''));
    }

    private function parseComponents(Worksheet $worksheet, int $row): array
    {
        $components = [];

        $slots = [
            [self::COLUMN_COMPONENT_1, self::COLUMN_COMPONENT_1_QUALITY, self::COLUMN_COMPONENT_1_AMOUNT],
            [self::COLUMN_COMPONENT_2, self::COLUMN_COMPONENT_2_QUALITY, self::COLUMN_COMPONENT_2_AMOUNT],
        ];

        foreach ($slots as [$nameCol, $qualityCol, $amountCol]) {
            $name = $this->getString($worksheet, $row, $nameCol);
            if ($name === null) {
                continue;
            }

            $components[] = [
                'name' => $name,
                'quality_name' => $this->getString($worksheet, $row, $qualityCol),
                'amount' => $this->getInt($worksheet, $row, $amountCol) ?? 0,
            ];
        }

        return $components;
    }

    private function parseWorkers(Worksheet $worksheet, int $row): array
    {
        $workers = [];

        $slots = [
            [self::COLUMN_WORKER_1, self::COLUMN_WORKER_1_LEVEL],
            [self::COLUMN_WORKER_2, self::COLUMN_WORKER_2_LEVEL],
            [self::COLUMN_WORKER_3, self::COLUMN_WORKER_3_LEVEL],
        ];

        foreach ($slots as [$keyCol, $levelCol]) {
            $workerKey = $this->getString($worksheet, $row, $keyCol);
            if ($workerKey === null) {
                continue;
            }

            $workers[] = [
                'worker_key' => $workerKey,
                'level_required' => $this->getInt($worksheet, $row, $levelCol),
            ];
        }

        return $workers;
    }

    private function parseResources(Worksheet $worksheet, int $row): array
    {
        $resources = [];

        foreach (self::RESOURCE_COLUMNS as $column => $resourceName) {
            $quantity = $this->getInt($worksheet, $row, $column);
            if ($quantity === null || $quantity <= 0) {
                continue;
            }

            $resources[] = [
                'resource_name' => $resourceName,
                'quantity' => $quantity,
            ];
        }

        return $resources;
    }

    private function parseCraftingUpgrades(Worksheet $worksheet, int $row): array
    {
        $upgrades = [];

        foreach (self::CRAFTING_UPGRADE_COLUMNS as $index => $cols) {
            $name = $this->getString($worksheet, $row, $cols['name']);
            $crafts = $this->getInt($worksheet, $row, $cols['crafts']);

            if ($name === null && $crafts === null) {
                continue;
            }

            $upgrades[] = [
                'upgrade_level' => $index + 1,
                'name' => $name,
                'crafts_required' => $crafts ?? 0,
            ];
        }

        return $upgrades;
    }

    private function parseStarforged(Worksheet $worksheet, int $row): array
    {
        $milestones = [];

        foreach (self::STARFORGED_COLUMNS as $index => $cols) {
            $name = $this->getString($worksheet, $row, $cols['name']);
            $crafts = $this->getInt($worksheet, $row, $cols['crafts']);

            if ($name === null && $crafts === null) {
                continue;
            }

            $milestones[] = [
                'milestone_number' => $index + 1,
                'name' => $name,
                'crafts_required' => $crafts ?? 0,
            ];
        }

        return $milestones;
    }

    private function parseAscension(Worksheet $worksheet, int $row): array
    {
        $upgrades = [];

        foreach (self::ASCENSION_COLUMNS as $index => $cols) {
            $name = $this->getString($worksheet, $row, $cols['name']);
            $shards = $this->getInt($worksheet, $row, $cols['shards']);

            if ($name === null && $shards === null) {
                continue;
            }

            $upgrades[] = [
                'upgrade_level' => $index + 1,
                'name' => $name,
                'shards_required' => $shards ?? 0,
            ];
        }

        return $upgrades;
    }

    private function parseTranscendence(Worksheet $worksheet, int $row): array
    {
        $upgrades = [];

        foreach (self::TRANSCENDENCE_COLUMNS as $index => $cols) {
            $name = $this->getString($worksheet, $row, $cols['name']);
            $seals = $this->getInt($worksheet, $row, $cols['seals']);

            if ($name === null && $seals === null) {
                continue;
            }

            $upgrades[] = [
                'upgrade_level' => $index + 1,
                'name' => $name,
                'seals_required' => $seals ?? 0,
            ];
        }

        return $upgrades;
    }

    private function getString(Worksheet $worksheet, int $row, string $column): ?string
    {
        $value = $worksheet->getCell($column.$row)->getValue();

        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        if ($value === '' || $value === '---') {
            return null;
        }

        return $value;
    }

    private function getInt(Worksheet $worksheet, int $row, string $column): ?int
    {
        $value = $worksheet->getCell($column.$row)->getValue();

        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        if ($value === '' || $value === '---') {
            return null;
        }

        return (int) $value;
    }

    private function getFloat(Worksheet $worksheet, int $row, string $column): ?float
    {
        $value = $worksheet->getCell($column.$row)->getValue();

        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        if ($value === '' || $value === '---') {
            return null;
        }

        return (float) $value;
    }

    private function getDateString(Worksheet $worksheet, int $row, string $column): ?string
    {
        $value = $worksheet->getCell($column.$row)->getValue();

        if ($value === null) {
            return null;
        }

        if (is_numeric($value)) {
            $timestamp = Date::excelToTimestamp($value);

            return date('Y-m-d H:i:s', $timestamp);
        }

        $value = trim((string) $value);

        if ($value === '' || $value === '---') {
            return null;
        }

        return $value;
    }
}
