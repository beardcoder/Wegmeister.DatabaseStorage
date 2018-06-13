<?php
namespace Wegmeister\DatabaseStorage\Finishers;

/**
 * This script belongs to the Neos Flow package "Wegmeister.DatabaseStorage".
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU Lesser General Public License, either version 3
 * of the License, or (at your option) any later version.
 *
 * The Neos project - inspiring people to share!
 */

use Neos\ContentRepository\Domain\Model\NodeData;
use Neos\ContentRepository\Domain\Repository\NodeDataRepository;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Persistence\Exception\IllegalObjectTypeException;
use Neos\Form\Core\Model\AbstractFinisher;

use Wegmeister\DatabaseStorage\Domain\Model\DatabaseStorage;
use Wegmeister\DatabaseStorage\Domain\Repository\DatabaseStorageRepository;

/**
 * A simple finisher that stores data into database
 */
class DatabaseStorageFinisher extends AbstractFinisher
{

    /**
     * @Flow\Inject
     * @var DatabaseStorageRepository
     */
    protected $storageRepository;

    /**
     * @Flow\Inject
     * @var NodeDataRepository
     */
    protected $nodeDataRepository;

    /**
     * @var array
     */
    protected $excludedNodeTypes = [];

    /**
     * Executes this finisher
     *
     * @see AbstractFinisher::execute()
     *
     * @return void
     * @throws IllegalObjectTypeException
     */
    protected function executeInternal()
    {
        $this->excludedNodeTypes = $this->parseOption('excludedNodeTypes');
        $formRuntime = $this->finisherContext->getFormRuntime();
        $formValues = $this->removeSections($formRuntime->getFormState()->getFormValues());

        $identifier = $this->parseOption('identifier');
        if (!$identifier) {
            $identifier = '__undefined__';
        }

        $dbStorage = new DatabaseStorage();
        $dbStorage
            ->setStorageidentifier($identifier)
            ->setProperties($formValues)
            ->setDateTime(new \DateTime());

        $this->storageRepository->add($dbStorage);
    }

    /**
     * Remove sections from formValues
     *
     * @param $formValues
     *
     * @return array
     */
    private function removeSections($formValues): array
    {
        foreach ($formValues as $key => $formValue) {
            /** @var \Neos\ContentRepository\Domain\Model\NodeData $nodeIdentifier */
            $nodeIdentifier = $this->nodeDataRepository->findByNodeIdentifier($key)->getFirst();
            if ($nodeIdentifier !== null && $this->isExcludedByOptions($nodeIdentifier)) {
                unset($formValues[$key]);
            }
        }

        return $formValues;
    }

    /**
     * @param NodeData $nodeIdentifier
     *
     * @return bool
     */
    private function isExcludedByOptions(NodeData $nodeIdentifier): bool
    {
        if (in_array($nodeIdentifier->getNodeType()->getName(), $this->excludedNodeTypes)) {
            return true;
        }

        return false;
    }
}
