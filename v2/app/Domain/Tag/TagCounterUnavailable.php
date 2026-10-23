<?php

namespace App\Domain\Tag;

use DomainException;

/** 担当者のタグ番号の行が使えない (他店舗の担当者など)。 */
class TagCounterUnavailable extends DomainException {}
