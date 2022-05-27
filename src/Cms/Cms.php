<?php

namespace Adshares\CmsBundle\Cms;

class Cms
{
    private bool $editMode = false;

    public function setEditMode(bool $editMode): self
    {
        $this->editMode = $editMode;
        return $this;
    }

    public function isEditMode(): bool
    {
        return $this->editMode;
    }
}
