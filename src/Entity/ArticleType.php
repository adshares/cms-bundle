<?php

namespace Adshares\CmsBundle\Entity;

enum ArticleType: string
{
    case Article = 'article';
    case Event = 'event';
    case FAQ = 'faq';
    case Notice = 'notice';
}
