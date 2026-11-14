<?php

namespace SimpleSoftwareIO\QrCode\DataTypes;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Exception;
use InvalidArgumentException;

/**
 * A calendar event that phones offer to add to their calendar: a bare iCalendar VEVENT, as
 * QR code readers expect it (ZXing among them), with the values of RFC 5545 and its text
 * escaping, but no VCALENDAR around it. Times are written in UTC, so that a reader needs no
 * time zone definition.
 */
class Calendar implements DataTypeInterface
{
    /**
     * The fields an event takes.
     *
     * @var string[]
     */
    protected const FIELDS = ['summary', 'start', 'end', 'timezone', 'allDay', 'location', 'description', 'url'];

    /**
     * The separator between the lines of the event.
     *
     * @var string
     */
    protected $separator = "\r\n";

    /**
     * The lines of the event, between BEGIN:VEVENT and END:VEVENT.
     *
     * @var string[]
     */
    protected $lines = [];

    /**
     * Generates the DataType Object and sets all of its properties.
     *
     * @param array $arguments
     * @throws InvalidArgumentException
     */
    public function create(array $arguments)
    {
        $this->setProperties($arguments[0] ?? null);
    }

    /**
     * Returns the correct QrCode format.
     *
     * @return string
     */
    public function __toString()
    {
        return implode($this->separator, ['BEGIN:VEVENT', ...$this->lines, 'END:VEVENT']);
    }

    /**
     * Checks and sets the fields of the event.
     *
     * @param mixed $event
     * @throws InvalidArgumentException
     */
    protected function setProperties($event)
    {
        if (! is_array($event)) {
            throw new InvalidArgumentException('calendar() takes an array with at least the summary and the start of the event.');
        }

        if ($unknown = array_diff(array_keys($event), self::FIELDS)) {
            throw new InvalidArgumentException(
                'Unknown calendar field: '.implode(', ', $unknown).'. The fields are '.implode(', ', self::FIELDS).'.'
            );
        }

        if (! isset($event['summary']) || trim((string) $event['summary']) === '') {
            throw new InvalidArgumentException('A calendar event needs a summary.');
        }

        if (! isset($event['start']) || $event['start'] === '') {
            throw new InvalidArgumentException('A calendar event needs a start.');
        }

        // A string such as 'false' must not make an all-day event.
        $allDay = filter_var($event['allDay'] ?? false, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        if ($allDay === null) {
            throw new InvalidArgumentException('The allDay field of a calendar event must be true or false.');
        }

        $timezone = $this->timezone($event['timezone'] ?? date_default_timezone_get());
        $start = $this->date($event['start'], $timezone, 'start');
        $end = isset($event['end']) && $event['end'] !== '' ? $this->date($event['end'], $timezone, 'end') : null;

        if ($end !== null && ($allDay ? $end->format('Ymd') < $start->format('Ymd') : $end < $start)) {
            throw new InvalidArgumentException('The end of a calendar event cannot come before its start.');
        }

        $this->lines = ['SUMMARY:'.$this->escape($event['summary'])];

        if ($allDay) {
            // Dates as they are, in no time zone. DTEND is the day after the last one.
            $this->lines[] = 'DTSTART;VALUE=DATE:'.$start->format('Ymd');

            if ($end !== null) {
                $this->lines[] = 'DTEND;VALUE=DATE:'.$end->modify('+1 day')->format('Ymd');
            }
        } else {
            $utc = new DateTimeZone('UTC');
            $this->lines[] = 'DTSTART:'.$start->setTimezone($utc)->format('Ymd\THis\Z');

            if ($end !== null) {
                $this->lines[] = 'DTEND:'.$end->setTimezone($utc)->format('Ymd\THis\Z');
            }
        }

        foreach (['location' => 'LOCATION', 'description' => 'DESCRIPTION'] as $field => $property) {
            if (isset($event[$field]) && (string) $event[$field] !== '') {
                $this->lines[] = $property.':'.$this->escape($event[$field]);
            }
        }

        if (isset($event['url']) && (string) $event['url'] !== '') {
            if (filter_var($event['url'], FILTER_VALIDATE_URL) === false) {
                throw new InvalidArgumentException("The url of a calendar event must be a valid URL. {$event['url']} is not.");
            }

            $this->lines[] = 'URL:'.$event['url'];
        }
    }

    /**
     * Reads a date: a DateTimeInterface, or a string in the time zone of the event.
     *
     * @param mixed $date
     * @param DateTimeZone $timezone
     * @param string $field
     * @return DateTimeImmutable
     * @throws InvalidArgumentException
     */
    protected function date($date, DateTimeZone $timezone, string $field): DateTimeImmutable
    {
        if ($date instanceof DateTimeInterface) {
            return DateTimeImmutable::createFromInterface($date);
        }

        try {
            if (! is_string($date)) {
                throw new InvalidArgumentException;
            }

            return new DateTimeImmutable($date, $timezone);
        } catch (Exception $e) {
            $given = is_scalar($date) ? (string) $date : get_debug_type($date);

            throw new InvalidArgumentException("The {$field} of a calendar event must be a date string or a DateTimeInterface. {$given} is not.");
        }
    }

    /**
     * Reads a time zone identifier.
     *
     * @param mixed $timezone
     * @return DateTimeZone
     * @throws InvalidArgumentException
     */
    protected function timezone($timezone): DateTimeZone
    {
        if ($timezone instanceof DateTimeZone) {
            return $timezone;
        }

        try {
            return new DateTimeZone((string) $timezone);
        } catch (Exception $e) {
            throw new InvalidArgumentException("{$timezone} is not a time zone, such as Europe/Paris.");
        }
    }

    /**
     * Escapes a text value: backslashes, semicolons, commas and line breaks.
     *
     * @param mixed $text
     * @return string
     */
    protected function escape($text): string
    {
        return str_replace(['\\', ';', ',', "\r\n", "\r", "\n"], ['\\\\', '\;', '\,', '\n', '\n', '\n'], (string) $text);
    }
}
