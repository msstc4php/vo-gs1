<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Gs1\ElementString;

/**
 * The AIDC media types ICCBBA has assigned for AI (7241), GenSpecs 26.0, Table 3-12, by their two-digit value
 * ({@see ElementString::aidcMediaType()}). Values outside `01`-`10` (not used, reserved, local or national use) have
 * no case; new assignments may add cases.
 *
 * @see https://ref.gs1.org/standards/genspecs/ GS1 General Specifications 26.0, section 3.8.23, Tables 3-11 and 3-12
 */
enum AidcMediaType: string
{
    case Wristband = '01';
    case OrderForm = '02';
    case SampleTube = '03';
    case WorkingList = '04';
    case TestReport = '05';
    case DeliveryNote = '06';
    case IntendedRecipientLabel = '07';
    case LabelAffixedToProduct = '08';
    case IdentityCard = '09';
    case ClinicalNotes = '10';

    /**
     * The name in Table 3-12, verbatim.
     *
     * @return non-empty-string
     */
    public function label(): string
    {
        return match ($this) {
            self::Wristband => 'Wristband',
            self::OrderForm => 'Order form',
            self::SampleTube => 'Sample Tube',
            self::WorkingList => 'Working list / Lab list / form',
            self::TestReport => 'Test report',
            self::DeliveryNote => 'Delivery note / issue documentation',
            self::IntendedRecipientLabel => 'Intended recipient label (attached to container)',
            self::LabelAffixedToProduct => 'Label affixed to product',
            self::IdentityCard => 'Identity card',
            self::ClinicalNotes => 'Clinical or progress notes',
        };
    }
}
