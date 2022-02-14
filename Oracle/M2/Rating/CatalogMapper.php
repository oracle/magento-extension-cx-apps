<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Rating;

class CatalogMapper extends \Oracle\M2\Product\CatalogMapperAbstract
{
    private static $_codes = [
        'last_created' => 'Last Review Created',
        'avg_rating' => 'Avg. Review Rating',
        'avg_rating_approved' => 'Avg. Approved Review Rating',
        'review_cnt' => 'Number of Reviews'
    ];

    private static $_defaultCodes = [
        'average_rating' => 'avg_rating',
        'review_count' => 'review_cnt'
    ];

    private static $_typeCodes = [
        'last_created' => 'date',
        'avg_rating' => 'double',
        'avg_rating_approved' => 'double',
        'review_cnt' => 'integer'
    ];

    /**
     * @see parent
     */
    public function getExtraFields()
    {
        return self::$_codes;
    }

    /**
     * @see parent
     */
    public function getDefaultFields()
    {
        return self::$_defaultCodes;
    }

    /**
     * @see parent
     */
    public function getFieldAttributes()
    {
        return self::$_typeCodes;
    }
}
