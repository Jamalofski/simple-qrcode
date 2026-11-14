<?php

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SimpleSoftwareIO\QrCode\DataTypes\Calendar;
use SimpleSoftwareIO\QrCode\Generator;
use Zxing\QrReader;

class CalendarTest extends TestCase
{
    private function calendar(array $event): string
    {
        $calendar = new Calendar;
        $calendar->create([$event]);

        return (string) $calendar;
    }

    public function test_it_writes_an_event_in_utc()
    {
        $this->assertSame(
            "BEGIN:VEVENT\r\nSUMMARY:Product launch\r\nDTSTART:20261201T170000Z\r\nDTEND:20261201T190000Z\r\n"
            ."LOCATION:Paris\r\nDESCRIPTION:Doors open at 17:30.\r\nURL:https://example.com/launch\r\nEND:VEVENT",
            $this->calendar([
                'summary' => 'Product launch',
                'start' => '2026-12-01 18:00',
                'end' => '2026-12-01 20:00',
                'timezone' => 'Europe/Paris',
                'location' => 'Paris',
                'description' => 'Doors open at 17:30.',
                'url' => 'https://example.com/launch',
            ])
        );
    }

    public function test_the_year_and_the_minutes_are_right()
    {
        // Upstream pull request #202 wrote the year twice and the month in place of the minutes.
        $this->assertStringContainsString(
            'DTSTART:19931105T094500Z',
            $this->calendar(['summary' => 'Meeting', 'start' => '1993-11-05 09:45', 'timezone' => 'UTC'])
        );
    }

    public function test_a_date_time_object_keeps_its_own_time_zone()
    {
        $start = new DateTimeImmutable('2026-07-14 10:00', new DateTimeZone('America/New_York'));

        $this->assertStringContainsString(
            'DTSTART:20260714T140000Z',
            $this->calendar(['summary' => 'Meeting', 'start' => $start, 'timezone' => 'Asia/Tokyo'])
        );
    }

    public function test_without_a_time_zone_the_default_one_of_php_applies()
    {
        $default = date_default_timezone_get();
        date_default_timezone_set('Europe/Paris');

        try {
            $this->assertStringContainsString('DTSTART:20260115T090000Z', $this->calendar(['summary' => 'Meeting', 'start' => '2026-01-15 10:00']));
        } finally {
            date_default_timezone_set($default);
        }
    }

    public function test_an_all_day_event_has_dates_and_ends_the_day_after_its_last_day()
    {
        $this->assertSame(
            "BEGIN:VEVENT\r\nSUMMARY:Conference\r\nDTSTART;VALUE=DATE:20261201\r\nDTEND;VALUE=DATE:20261204\r\nEND:VEVENT",
            $this->calendar(['summary' => 'Conference', 'start' => '2026-12-01', 'end' => '2026-12-03', 'allDay' => true])
        );

        $this->assertSame(
            "BEGIN:VEVENT\r\nSUMMARY:Holiday\r\nDTSTART;VALUE=DATE:20261225\r\nEND:VEVENT",
            $this->calendar(['summary' => 'Holiday', 'start' => '2026-12-25', 'allDay' => true, 'end' => ''])
        );

        // As a string, 'false' is false.
        $this->assertStringContainsString(
            'DTSTART:20261225T090000Z',
            $this->calendar(['summary' => 'Holiday', 'start' => '2026-12-25 10:00', 'timezone' => 'Europe/Paris', 'allDay' => 'false'])
        );
    }

    public function test_texts_are_escaped()
    {
        $this->assertStringContainsString(
            'SUMMARY:Lunch\; then talks\, Q&A \\\\ more\nSecond line\nThird line',
            $this->calendar(['summary' => "Lunch; then talks, Q&A \\ more\r\nSecond line\nThird line", 'start' => '2026-12-01 12:00'])
        );
    }

    public static function invalidEvents(): array
    {
        return [
            'no array' => [null, 'takes an array'],
            'no summary' => [['start' => '2026-12-01 10:00'], 'needs a summary'],
            'empty summary' => [['summary' => ' ', 'start' => '2026-12-01 10:00'], 'needs a summary'],
            'no start' => [['summary' => 'Meeting'], 'needs a start'],
            'empty start' => [['summary' => 'Meeting', 'start' => ''], 'needs a start'],
            'invalid all day' => [['summary' => 'Meeting', 'start' => '2026-12-01', 'allDay' => 'maybe'], 'must be true or false'],
            'invalid start' => [['summary' => 'Meeting', 'start' => 'tomorrowish'], 'The start of a calendar event must be a date'],
            'invalid end' => [['summary' => 'Meeting', 'start' => '2026-12-01 10:00', 'end' => ['2026']], 'The end of a calendar event must be a date'],
            'end before start' => [['summary' => 'Meeting', 'start' => '2026-12-01 10:00', 'end' => '2026-12-01 09:00'], 'cannot come before its start'],
            'last day before first day' => [['summary' => 'Trip', 'start' => '2026-12-02', 'end' => '2026-12-01', 'allDay' => true], 'cannot come before its start'],
            'invalid time zone' => [['summary' => 'Meeting', 'start' => '2026-12-01 10:00', 'timezone' => 'Paris'], 'Paris is not a time zone'],
            'invalid url' => [['summary' => 'Meeting', 'start' => '2026-12-01 10:00', 'url' => "https://example.com\r\nX-INJECTED:1"], 'must be a valid URL'],
            'field of upstream pull request #202' => [['summary' => 'Meeting', 'startDateTime' => '2026-12-01 10:00'], 'Unknown calendar field: startDateTime'],
        ];
    }

    #[DataProvider('invalidEvents')]
    public function test_an_invalid_event_throws_a_clear_error($event, string $message)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($message);

        (new Calendar)->create([$event]);
    }

    public function test_the_generator_has_a_calendar_helper_that_decodes()
    {
        $generator = new class extends Generator
        {
            protected function imagickIsAvailable(): bool
            {
                return false;
            }
        };

        $event = ['summary' => 'Réunion', 'start' => '2026-12-01 10:00', 'end' => '2026-12-01 11:00', 'timezone' => 'Europe/Paris'];
        $png = (string) $generator->format('png')->size(400)->margin(2)->encoding('UTF-8')->calendar($event);

        $this->assertSame($this->calendar($event), (new QrReader($png, QrReader::SOURCE_TYPE_BLOB, false))->text());
    }
}
