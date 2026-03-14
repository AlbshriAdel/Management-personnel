<?php

namespace App\DTO\Settings\Lock\Subsettings;

use App\DTO\AbstractDTO;
use Exception;

/**
 * Transfers base data regarding module lock state in setting panel
 *
 * Class SettingsModuleLockDTO
 * @package App\DTO\Settings\Lock\Subsettings
 */
class SettingsModuleLockDTO extends AbstractDTO
{
    const KEY_NAME          = "name";
    const KEY_IS_LOCKED     = "isLocked";
    const KEY_DISPLAY_ORDER = "displayOrder";
    const KEY_DISPLAY_NAME  = "displayName";
    const KEY_ACTIVE        = "active";

    /**
     * @var string $name
     */
    private string $name;

    /**
     * @var bool $locked
     */
    private bool $locked;

    /**
     * @var int|null $displayOrder
     */
    private ?int $displayOrder = null;

    /**
     * @var string|null $displayName
     */
    private ?string $displayName = null;

    /**
     * @var bool $active
     */
    private bool $active = true;

    public function __construct(
        ?string $name = null,
        ?bool   $locked = null,
    ) {
        if (!is_null($locked)) {
            $this->locked = $locked;
        }

        if(!is_null($name)){
            $this->name = $name;
        }
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return bool
     */
    public function isLocked(): bool
    {
        return $this->locked;
    }

    /**
     * @param bool $locked
     */
    public function setLocked(bool $locked): void
    {
        $this->locked = $locked;
    }

    public function getDisplayOrder(): ?int
    {
        return $this->displayOrder;
    }

    public function setDisplayOrder(?int $displayOrder): void
    {
        $this->displayOrder = $displayOrder;
    }

    public function getDisplayName(): ?string
    {
        return $this->displayName;
    }

    public function setDisplayName(?string $displayName): void
    {
        $this->displayName = $displayName;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): void
    {
        $this->active = $active;
    }

    /**
     * @param string $json
     * @return SettingsModuleLockDTO
     * @throws Exception
     */
    public static function fromJson(string $json): self
    {
        $dataArray = json_decode($json, true);
        $dto       = self::fromArray($dataArray);
        return $dto;
    }

    /**
     * @param array $dataArray
     * @return SettingsModuleLockDTO
     * @throws Exception
     */
    public static function fromArray(array $dataArray): self
    {
        $name      = self::checkAndGetKey($dataArray, self::KEY_NAME);
        $IsVisible = self::checkAndGetKey($dataArray, self::KEY_IS_LOCKED);

        $dto = new SettingsModuleLockDTO();
        $dto->setName($name);
        $dto->setLocked($IsVisible);
        $dto->setDisplayOrder($dataArray[self::KEY_DISPLAY_ORDER] ?? null);
        $dto->setDisplayName($dataArray[self::KEY_DISPLAY_NAME] ?? null);
        $dto->setActive($dataArray[self::KEY_ACTIVE] ?? true);

        return $dto;
    }

    /**
     * Returns array representation of current dto
     *
     * @return array
     */
    public function toArray(): array
    {
        $arr = [
           self::KEY_NAME      => $this->getName(),
           self::KEY_IS_LOCKED => $this->isLocked(),
        ];
        if ($this->displayOrder !== null) {
            $arr[self::KEY_DISPLAY_ORDER] = $this->displayOrder;
        }
        if ($this->displayName !== null) {
            $arr[self::KEY_DISPLAY_NAME] = $this->displayName;
        }
        $arr[self::KEY_ACTIVE] = $this->active;
        return $arr;
    }

}