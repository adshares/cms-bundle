<?php

namespace Adshares\CmsBundle\Cms;

class Cms
{
    private bool $editMode = false;
    private ?string $route = null;
    private array $routeParams = [];

    public function setEditMode(bool $editMode): self
    {
        $this->editMode = $editMode;
        return $this;
    }

    public function isEditMode(): bool
    {
        return $this->editMode;
    }

    public function setRoute(string $route, array $params = []): self
    {
        $this->route = $route;
        $this->routeParams = $params;
        return $this;
    }

    public function getRoute(): ?string
    {
        return $this->route;
    }

    public function getRouteParams(): array
    {
        return $this->routeParams;
    }
}
