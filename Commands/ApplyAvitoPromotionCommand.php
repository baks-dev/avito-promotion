<?php
/*
 *  Copyright 2024.  Baks.dev <admin@baks.dev>
 *  
 *  Permission is hereby granted, free of charge, to any person obtaining a copy
 *  of this software and associated documentation files (the "Software"), to deal
 *  in the Software without restriction, including without limitation the rights
 *  to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 *  copies of the Software, and to permit persons to whom the Software is furnished
 *  to do so, subject to the following conditions:
 *  
 *  The above copyright notice and this permission notice shall be included in all
 *  copies or substantial portions of the Software.
 *  
 *  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 *  IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 *  FITNESS FOR A PARTICULAR PURPOSE AND NON INFRINGEMENT. IN NO EVENT SHALL THE
 *  AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 *  LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 *  OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 *  THE SOFTWARE.
 *
 */

declare(strict_types=1);

namespace BaksDev\Avito\Promotion\Commands;

use BaksDev\Avito\Promotion\Messenger\Schedules\AvitoProductPromotionMessage;
use BaksDev\Avito\Promotion\Repository\AllAvitoPromotionByPromotionCompany\AllAvitoPromotionByPromotionCompanyRepository;
use BaksDev\Avito\Promotion\Repository\AllAvitoPromotionCompanyByProfile\AllAvitoPromotionCompanyByProfileInterface;
use BaksDev\Avito\Repository\AllUserProfilesByActiveToken\AllUserProfilesByTokenRepository;
use BaksDev\Core\Messenger\MessageDispatchInterface;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'baks:avito-promotion:apply',
    description: 'Получает рекламные продукты Авито и применят услуги продвижения'
)]
class ApplyAvitoPromotionCommand extends Command
{
    private SymfonyStyle $io;

    public function __construct(
        private readonly MessageDispatchInterface $messageDispatch,
        private readonly AllUserProfilesByTokenRepository $allProfilesByToken,
        private readonly AllAvitoPromotionCompanyByProfileInterface $allPromotionByProfile,
        private readonly AllAvitoPromotionByPromotionCompanyRepository $allAvitoPromotionByPromotionCompanyRepository,
    )
    {
        parent::__construct();
    }


    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);

        /** Получаем активные токены авторизации профилей Yandex Market */
        $profiles = $this
            ->allProfilesByToken
            ->findProfilesByActiveToken();

        if(false === $profiles->valid())
        {
            $this->io->error('Токены авторизации Avito не найдены');
            return Command::FAILURE;
        }

        $profiles = iterator_to_array($profiles);

        $helper = $this->getHelper('question');

        $questions[] = 'Все';

        foreach($profiles as $quest)
        {
            $questions[] = $quest->getAttr();
        }

        $question = new ChoiceQuestion(
            'Профиль пользователя',
            $questions,
            0
        );

        $profileName = $helper->ask($input, $output, $question);

        if($profileName === 'Все')
        {
            /** @var UserProfileUid $profile */
            foreach($profiles as $profile)
            {
                $this->applyPromotion($profile);
            }
        }
        else
        {
            $UserProfileUid = null;

            foreach($profiles as $profile)
            {
                if($profile->getAttr() === $profileName)
                {
                    /* Присваиваем профиль пользователя */
                    $UserProfileUid = $profile;
                    break;
                }
            }

            if($UserProfileUid)
            {
                $this->applyPromotion($UserProfileUid);
            }

        }

        $this->io->success('Рекламная компания успешно обновлена');

        return Command::SUCCESS;
    }

    private function applyPromotion(UserProfileUid $profile): void
    {
        $this->io->note(sprintf('Обновляем рекламную компанию для профиля: %s', $profile->getAttr()));

        $allPromoCompanies = $this->allPromotionByProfile
            ->profile($profile)
            ->find();

        if(false === $allPromoCompanies)
        {
            $this->io->warning(sprintf('Не найдено ни одной рекламной компании для профиля: %s', $profile));
            return;
        }

        foreach($allPromoCompanies as $promoCompany)
        {
            $allAvitoProductPromotion = $this->allAvitoPromotionByPromotionCompanyRepository
                ->byPromotionCompany($promoCompany['id'])
                ->find();

            if(false === $allAvitoProductPromotion)
            {
                continue;
            }

            foreach($allAvitoProductPromotion as $promoProduct)
            {
                $this->messageDispatch->dispatch(new AvitoProductPromotionMessage ($promoProduct['promo_product_id']));
            }
        }
    }
}
