<?php
/**
 *  Copyright (c) Enalean, 2017. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Tuleap\Tracker\Report\Query\Advanced;

use Tracker_FormElementFactory;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Field;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Metadata;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Visitor;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\InvalidFieldException;
use Tuleap\Tracker\Report\Query\Advanced\InvalidMetadata\InvalidMetadataForComparisonException;

class RealInvalidSearchableCollectorVisitor implements Visitor
{
    const SUPPORTED_NAME = '@comments';

    /**
     * @var Tracker_FormElementFactory
     */
    private $form_element_factory;

    public function __construct(Tracker_FormElementFactory $form_element_factory)
    {
        $this->form_element_factory = $form_element_factory;
    }

    public function visitField(
        Field $searchable_field,
        RealInvalidSearchableCollectorParameters $parameters
    ) {
        $field = $this->form_element_factory->getUsedFormElementFieldByNameForUser(
            $parameters->getInvalidSearchablesCollectorParameters()->getTracker()->getId(),
            $searchable_field->getName(),
            $parameters->getInvalidSearchablesCollectorParameters()->getUser()
        );

        if (! $field) {
            $parameters->getInvalidSearchablesCollectorParameters()->getInvalidSearchablesCollection()->addNonexistentSearchable(
                $searchable_field->getName()
            );
        } else {
            try {
                $parameters->getCheckerProvider()
                    ->getInvalidFieldChecker($field)
                    ->checkFieldIsValidForComparison($parameters->getComparison(), $field);
            } catch (InvalidFieldException $exception) {
                $parameters->getInvalidSearchablesCollectorParameters()->getInvalidSearchablesCollection()->addInvalidSearchableError(
                    $exception->getMessage()
                );
            }
        }
    }

    public function visitMetadata(
        Metadata $metadata,
        RealInvalidSearchableCollectorParameters $parameters
    ) {
        if ($metadata->getName() !== self::SUPPORTED_NAME) {
            $parameters->getInvalidSearchablesCollectorParameters()->getInvalidSearchablesCollection()->addNonexistentSearchable(
                $metadata->getName()
            );

            return;
        }

        try {
            $parameters->getMetadataChecker()->checkMetaDataIsValid($metadata, $parameters->getComparison());
        } catch (InvalidMetadataForComparisonException $exception) {
            $parameters->getInvalidSearchablesCollectorParameters()->getInvalidSearchablesCollection()->addInvalidSearchableError(
                $exception->getMessage()
            );
        } catch (InvalidFieldException $exception) {
            $parameters->getInvalidSearchablesCollectorParameters()->getInvalidSearchablesCollection()->addInvalidSearchableError(
                $exception->getMessage()
            );
        }
    }
}
