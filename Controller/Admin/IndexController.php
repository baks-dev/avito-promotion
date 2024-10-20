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
 */

declare(strict_types=1);

namespace BaksDev\Avito\Promotion\Controller\Admin;

use BaksDev\Avito\Promotion\Repository\AllAvitoPromotionCompanyByProfile\AllAvitoPromotionCompanyByProfileInterface;
use BaksDev\Core\Controller\AbstractController;
use BaksDev\Core\Form\Search\SearchDTO;
use BaksDev\Core\Form\Search\SearchForm;
use BaksDev\Core\Listeners\Event\Security\RoleSecurity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;

#[AsController]
#[RoleSecurity('ROLE_AVITO_PROMOTION_INDEX')]
final class IndexController extends AbstractController
{
    #[Route(
        path: '/admin/avito-promotion/companies/{page<\d+>}',
        name: 'admin.company.index',
        methods: ['GET', 'POST'],
    )]
    public function index(
        Request $request,
        AllAvitoPromotionCompanyByProfileInterface $allPromotionByProfile,
        int $page = 0,
    ): Response
    {
        // Поиск
        $search = new SearchDTO();
        $searchForm = $this
            ->createForm(
                type: SearchForm::class,
                data: $search,
                options: [
                    'action' => $this->generateUrl('avito-promotion:admin.company.index')
                ])
            ->handleRequest($request);

        $profile = $this->getCurrentProfileUid();

        $allPromotion = $allPromotionByProfile
            ->profile($profile)
            ->find();

        return $this->render(
            [
                'query' => $allPromotion,
                'profile' => $profile,
                'search' => $searchForm->createView(),
            ],
        );
    }
}
