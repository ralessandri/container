<?php

declare(strict_types=1);

namespace B13\Container\Backend\Grid;

/*
 * This file is part of TYPO3 CMS-based extension "container" by b13.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 */

use B13\Container\Domain\Model\Container;
use B13\Container\Tca\Registry;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\View\BackendLayout\Grid\GridColumn;
use TYPO3\CMS\Backend\View\PageLayoutContext;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ContainerGridColumn extends GridColumn
{
    protected $container;

    protected $registry;

    public function __construct(PageLayoutContext $context, array $columnDefinition, Container $container, Registry $registry = null)
    {
        parent::__construct($context, $columnDefinition);
        $this->container = $container;
        if ($registry === null) {
            trigger_error('Registry is required as constructor argument', E_USER_DEPRECATED);
            $registry = GeneralUtility::makeInstance(Registry::class);
        }
        $this->registry = $registry;
    }

    public function getContainerUid(): int
    {
        return $this->container->getUid();
    }

    public function getTitle(): string
    {
        return (string)$this->getLanguageService()->sL($this->getColumnName());
    }

    public function getAllowNewContent(): bool
    {
        if ($this->container->getLanguage() > 0 && $this->container->isConnectedMode()) {
            return false;
        }
        return true;
    }

    public function isActive(): bool
    {
        // yes we are active
        return true;
    }

    public function getNewContentUrl(): string
    {
        $pageId = $this->context->getPageId();
        $target = $this->container->getFirstNewContentElementTarget($this->getColumnNumber(), $this->registry);
        $urlParameters = [
            'id' => $pageId,
            'sys_language_uid' => $this->container->getLanguage(),
            'colPos' => $this->getColumnNumber(),
            'tx_container_parent' => $this->container->getUid(),
            'uid_pid' => $pageId,
            'returnUrl' => GeneralUtility::getIndpEnv('REQUEST_URI')
        ];
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        return (string)$uriBuilder->buildUriFromRoute('new_content_element_wizard', $urlParameters);
    }
}
