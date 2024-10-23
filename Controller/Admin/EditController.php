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

namespace BaksDev\Avito\Promotion\Controller\Admin;

use BaksDev\Avito\Promotion\Entity\Event\AvitoPromotionEvent;
use BaksDev\Avito\Promotion\UseCase\NewEdit\AvitoPromotionDTO;
use BaksDev\Avito\Promotion\UseCase\NewEdit\AvitoPromotionForm;
use BaksDev\Avito\Promotion\UseCase\NewEdit\AvitoPromotionHandler;
use BaksDev\Core\Controller\AbstractController;
use BaksDev\Core\Listeners\Event\Security\RoleSecurity;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
#[RoleSecurity('ROLE_AVITO_PROMOTION_EDIT')]
final class EditController extends AbstractController
{
    #[Route(
        path: '/admin/avito-promotion/company/edit/{id}',
        name: 'admin.company.edit',
        methods: ['GET', 'POST'],
    )]
    public function edit(
        Request $request,
        #[MapEntity] AvitoPromotionEvent $event,
        AvitoPromotionHandler $handler,
    ): Response
    {

        $editDTO = new AvitoPromotionDTO();

        $event->getDto($editDTO);

        $form = $this
            ->createForm(
                type: AvitoPromotionForm::class,
                data: $editDTO,
                options: [
                    'action' => $this
                        ->generateUrl(
                            route: 'avito-promotion:admin.company.edit',
                            parameters: ['id' => $event->getEvent(),]
                        ),
                ],
            )
            ->handleRequest($request);

        if($form->isSubmitted() && $form->isValid() && $form->has('avito_promotion_add'))
        {
            $this->refreshTokenForm($form);

            $result = $handler->handle($editDTO);

            if($result)
            {
                $this->addFlash('page.edit', 'success.edit', 'avito-promotion.admin');

                return $this->redirectToRoute('avito-promotion:admin.company.index');
            }

            $this->addFlash('page.edit', 'danger.update', 'avito-promotion.admin', $result);

            return $this->redirectToReferer();
        }

        return $this->render(['form' => $form->createView()]);

    }
}
