<?php

declare(strict_types=1);

namespace BaksDev\Avito\Promotion\Entity\Modify;

use BaksDev\Avito\Promotion\Entity\Event\AvitoPromotionEvent;
use BaksDev\Core\Entity\EntityEvent;
use BaksDev\Core\Type\Ip\IpAddress;
use BaksDev\Core\Type\Modify\Modify\ModifyActionNew;
use BaksDev\Core\Type\Modify\Modify\ModifyActionUpdate;
use BaksDev\Core\Type\Modify\ModifyAction;
use BaksDev\Users\User\Entity\User;
use BaksDev\Users\User\Type\Id\UserUid;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'avito_promotion_event_modify')]
#[ORM\Index(columns: ['action'])]
class AvitoPromotionEventModify extends EntityEvent
{
    /** ID события */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    #[ORM\Id]
    #[ORM\OneToOne(targetEntity: AvitoPromotionEvent::class, inversedBy: 'modify')]
    #[ORM\JoinColumn(name: 'event', referencedColumnName: 'id')]
    private AvitoPromotionEvent $event;

    /** Модификатор */
    #[Assert\NotBlank]
    #[ORM\Column(type: ModifyAction::TYPE)]
    private ModifyAction $action;

    /** Дата */
    #[Assert\NotBlank]
    #[ORM\Column(name: 'mod_date', type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $modDate;

    /** ID пользователя  */
    #[ORM\Column(type: UserUid::TYPE, nullable: true)]
    private ?UserUid $usr = null;

    /** Ip адрес */
    #[Assert\NotBlank]
    #[ORM\Column(type: IpAddress::TYPE)]
    private IpAddress $ip;

    /** User-agent */
    #[Assert\NotBlank]
    #[ORM\Column(type: Types::TEXT)]
    private string $agent;

    public function __construct(AvitoPromotionEvent $event)
    {
        $this->event = $event;
        $this->modDate = new DateTimeImmutable();
        $this->ip = new IpAddress('127.0.0.1');
        $this->agent = 'console';
        $this->action = new ModifyAction(ModifyActionNew::class);
    }

    public function __clone(): void
    {
        $this->modDate = new DateTimeImmutable();
        $this->action = new ModifyAction(ModifyActionUpdate::class);
        $this->ip = new IpAddress('127.0.0.1');
        $this->agent = 'console';
    }

    public function __toString(): string
    {
        return (string)$this->event;
    }

    public function getDto($dto): mixed
    {
        if ($dto instanceof AvitoPromotionEventModifyInterface)
        {
            return parent::getDto($dto);
        }

        throw new InvalidArgumentException(sprintf('Class %s interface error', $dto::class));
    }

    public function setEntity($dto): mixed
    {
        if ($dto instanceof AvitoPromotionEventModifyInterface)
        {
            return parent::setEntity($dto);
        }

        throw new InvalidArgumentException(sprintf('Class %s interface error', $dto::class));
    }

    public function upModifyAgent(IpAddress $ip, string $agent): void
    {
        $this->ip = $ip;
        $this->agent = $agent;
        $this->modDate = new DateTimeImmutable();
    }

    public function setUsr(UserUid|User|null $user): void
    {
        $this->usr = $user instanceof User ? $user->getId() : $user;
    }

    public function equals(mixed $action): bool
    {
        return $this->action->equals($action);
    }
}