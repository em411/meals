<?php

namespace App\Mealz\MealBundle\Controller;

use App\Mealz\AccountingBundle\Entity\Transaction;
use App\Mealz\AccountingBundle\Entity\TransactionRepository;
use App\Mealz\MealBundle\Entity\Category;
use App\Mealz\MealBundle\Entity\CategoryRepository;
use App\Mealz\MealBundle\Entity\Meal;
use App\Mealz\MealBundle\Entity\MealRepository;
use App\Mealz\MealBundle\Entity\Participant;
use App\Mealz\MealBundle\Entity\ParticipantRepository;
use App\Mealz\MealBundle\Service\Doorman;
use App\Mealz\UserBundle\Entity\Profile;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;

abstract class BaseController extends AbstractController
{
    public static function getSubscribedServices(): array
    {
        $services = parent::getSubscribedServices();
        $services['logger'] = '?' . LoggerInterface::class;
        $services['mealz_meal.doorman'] = '?' . Doorman::class;
        $services['monolog.logger.balance'] = '?' . LoggerInterface::class;
        $services['translator'] = '?' . TranslatorInterface::class;

        return $services;
    }

    /**
     * @return MealRepository
     */
    protected function getMealRepository()
    {
        return $this->getDoctrine()->getRepository(Meal::class);
    }

    /**
     * @return ParticipantRepository
     */
    protected function getParticipantRepository()
    {
        return $this->getDoctrine()->getRepository(Participant::class);
    }

    /**
     * @return CategoryRepository
     */
    protected function getCategoryRepository()
    {
        return $this->getDoctrine()->getRepository(Category::class);
    }

    /**
     * @return TransactionRepository
     */
    protected function getTransactionRepository()
    {
        return $this->getDoctrine()->getRepository(Transaction::class);
    }

    /**
     * @return Doorman
     */
    protected function getDoorman()
    {
        return $this->get('mealz_meal.doorman');
    }

    protected function getProfile(): ?Profile
    {
        return $this->getUser() ? $this->getUser()->getProfile() : null;
    }

    /**
     * @param mixed  $message
     * @param string $severity "danger", "warning", "info", "success"
     */
    protected function addFlashMessage($message, string $severity): void
    {
        $this->get('session')->getFlashBag()->add($severity, $message);
    }

    protected function ajaxSessionExpiredRedirect(): JsonResponse
    {
        $message = $this->get('translator')->trans('session.expired', [], 'messages');
        $this->addFlashMessage($message, 'info');
        $response = [
            'redirect' => $this->generateUrl('MealzUserBundle_login'),
        ];

        return new JsonResponse($response);
    }

    /**
     * @param Exception $exc     Exception to log
     * @param string    $message Additional message
     * @param array     $context Name-value data describing the execution state when exception occurred
     */
    protected function logException(Throwable $exc, string $message = '', array $context = []): void
    {
        $excChain = ['message' => ('' === $message ? 'exception' : $message)];

        for ($i = 0; $exc; ++$i) {
            $excLog = ['type' => get_class($exc)];

            if (0 < $exc->getCode()) {
                $excLog['code'] = $exc->getCode();
            }

            $excLog['message'] = $exc->getMessage();
            $excLog['file'] = $exc->getFile() . ':' . $exc->getLine();

            $prev = $exc->getPrevious();
            if (null === $prev) {
                $excLog['trace'] = $exc->getTraceAsString();
            }

            $exc = $prev;
            $excChain['#' . $i] = $excLog;
        }

        $this->get('logger')->error(json_encode($excChain), $context);
    }
}
