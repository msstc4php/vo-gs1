<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Gs1\Marking\RU;

/**
 * The code structures the Government decrees and the operator («Честный знак») publish. A structure serves several
 * product groups; the group itself is not encoded in the code.
 *
 * @see docs/sources/GS1.md "Russian marking"
 */
enum MarkingCodeStructure: string
{
    /** Decree No. 224, p. 43: 14-digit product code, 7-character serial, 4-character encoded price, 4-character check code; no AIs. */
    case TobaccoConsumerPack = 'tobacco_consumer_pack';

    /** Decree No. 224, pp. 44-45 (group pack and set): AI (01), (21) of 7, (8005) and (93) of 4, then any further groups. */
    case TobaccoGroupPack = 'tobacco_group_pack';

    /** The «стандартная (длинная)» structure: AI (01), (21), (91) of 4 and (92) of 44 or 88. */
    case Standard = 'standard';

    /** The «укороченная» structure: AI (01), (21) and (93) of 4. */
    case Shortened = 'shortened';

    /**
     * @return non-empty-string
     */
    public function label(): string
    {
        return match ($this) {
            self::TobaccoConsumerPack => 'tobacco consumer pack',
            self::TobaccoGroupPack => 'tobacco group pack',
            self::Standard => 'standard',
            self::Shortened => 'shortened',
        };
    }
}
