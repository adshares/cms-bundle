<?php

namespace Adshares\CmsBundle\Entity;

enum ArticleType: string
{
    case Article = 'article';
    case Event = 'event';
    case FAQ = 'faq';
    case General = 'general';
    case Notice = 'notice';
    case Short = 'short';
    case Term = 'term';
    case Tutorial = 'tutorial';
}
