<?php
/**
 * The form finisher for the database storage.
 *
 * This file is part of the Flow Framework Package "Wegmeister.DatabaseStorage".
 *
 * PHP version 7
 *
 * @category Finisher
 * @package  Wegmeister\DatabaseStorage
 * @author   Benjamin Klix <benjamin.klix@die-wegmeister.com>
 * @license  https://github.com/die-wegmeister/Wegmeister.DatabaseStorage/blob/master/LICENSE GPL-3.0-or-later
 * @link     https://github.com/die-wegmeister/Wegmeister.DatabaseStorage
 */
namespace Wegmeister\DatabaseStorage\Finishers;

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
     * Instance of the database storage repository.
     *
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
