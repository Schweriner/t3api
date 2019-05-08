<?php

namespace SourceBroker\Restify\Dispatcher;

use Doctrine\Common\Annotations\AnnotationReader;
use SourceBroker\Restify\Annotation\ApiResource as ApiResourceAnnotation;
use SourceBroker\Restify\Domain\Model\AbstractOperation;
use SourceBroker\Restify\Domain\Model\ApiResource;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use TYPO3\CMS\Core\Routing\RouteNotFoundException;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;

/**
 * Class Bootstrap
 */
class Bootstrap
{
    /**
     * @throws RouteNotFoundException
     */
    public function process()
    {
        $apiResources = $this->getAllApiResources();
        $context = (new RequestContext())->fromRequest(Request::createFromGlobals());

        foreach ($apiResources as $apiResource) {
            try {
                $urlMatcher = new UrlMatcher($apiResource->getRoutes(), $context);
                $matchedRoute = $urlMatcher->match($context->getPathInfo());
                $this->processOperation($apiResource->getOperationByRouteName($matchedRoute['_route']));
            } catch (ResourceNotFoundException $resourceNotFoundException) {
            }
        }

        throw new RouteNotFoundException('Restify resource not found for current route', 1557217186441);
    }

    /**
     * @param AbstractOperation $operation
     */
    private function processOperation(AbstractOperation $operation)
    {
        DebuggerUtility::var_dump($operation, 'THIS OPERATION WILL BE PROCESSED');
        die();
    }

    /**
     * @return ApiResource[]
     * @todo move to more appropriate place
     * @todo add caching
     */
    private function getAllApiResources()
    {
        $domainModels = $this->getAllDomainModels();
        $annotationReader = new AnnotationReader();
        $apiResources = [];

        foreach ($domainModels as $domainModel) {
            /** @var ApiResourceAnnotation $apiResourceAnnotation */
            $apiResourceAnnotation = $annotationReader->getClassAnnotation(
                new \ReflectionClass($domainModel),
                ApiResourceAnnotation::class
            );

            if (!$apiResourceAnnotation) {
                continue;
            }

            $apiResources[] = new ApiResource($domainModel, $apiResourceAnnotation);
        }

        return $apiResources;
    }

    /**
     * @return string[]
     * @todo move to more appropriate place
     */
    private function getAllDomainModels()
    {
        foreach (glob(PATH_site.'typo3conf/ext/*/Classes/Domain/Model/*.php') as $domainModelClassFile) {
            require_once $domainModelClassFile;
        }

        return array_filter(
            get_declared_classes(),
            function($class) {
                return is_subclass_of($class, AbstractEntity::class);
            }
        );
    }
}
