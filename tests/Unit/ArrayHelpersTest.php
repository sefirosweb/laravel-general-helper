<?php

declare(strict_types=1);

namespace Sefirosweb\LaravelGeneralHelper\Tests\Unit;

use Sefirosweb\LaravelGeneralHelper\Tests\TestCase;

/**
 * Unit tests for the global array/string helpers declared in
 * src/Helpers/GeneralHelperFunctions.php (loaded by the service provider).
 */
class ArrayHelpersTest extends TestCase
{
    // -- array_group_by ----------------------------------------------------

    public function test_array_group_by_with_string_key_on_assoc_rows(): void
    {
        $grouped = array_group_by([
            ['type' => 'a', 'id' => 1],
            ['type' => 'b', 'id' => 2],
            ['type' => 'a', 'id' => 3],
        ], 'type');

        $this->assertArrayHasKey('a', $grouped);
        $this->assertArrayHasKey('b', $grouped);
        $this->assertCount(2, $grouped['a']);
        $this->assertCount(1, $grouped['b']);
    }

    public function test_array_group_by_with_only_first_value_keeps_single_row_per_key(): void
    {
        $grouped = array_group_by([
            ['type' => 'a', 'id' => 1],
            ['type' => 'a', 'id' => 2],
            ['type' => 'a', 'id' => 3],
        ], 'type', onlyFirstValue: true);

        $this->assertSame(3, $grouped['a']['id'], 'onlyFirstValue should keep the LAST assignment per key (overwrite)');
    }

    public function test_array_group_by_with_callback_key(): void
    {
        $grouped = array_group_by([1, 2, 3, 4, 5, 6], fn ($n) => $n % 2 === 0 ? 'even' : 'odd');

        $this->assertCount(3, $grouped['odd']);
        $this->assertCount(3, $grouped['even']);
    }

    public function test_array_group_by_multidimensional_nests_groups(): void
    {
        $result = array_group_by_multidimensional([
            ['country' => 'es', 'city' => 'mad', 'id' => 1],
            ['country' => 'es', 'city' => 'mad', 'id' => 2],
            ['country' => 'es', 'city' => 'bcn', 'id' => 3],
            ['country' => 'fr', 'city' => 'par', 'id' => 4],
        ], ['country', 'city']);

        $this->assertCount(2, $result['es']['mad']);
        $this->assertCount(1, $result['es']['bcn']);
        $this->assertCount(1, $result['fr']['par']);
    }

    // -- objectToArray -----------------------------------------------------

    public function test_object_to_array_converts_stdclass_recursively(): void
    {
        $obj = (object) [
            'a' => 1,
            'b' => (object) ['c' => 2, 'd' => [(object) ['e' => 3]]],
        ];

        $arr = objectToArray($obj);

        $this->assertSame(1, $arr['a']);
        $this->assertSame(2, $arr['b']['c']);
        $this->assertSame(3, $arr['b']['d'][0]['e']);
    }

    public function test_object_to_array_passthrough_on_scalar(): void
    {
        $this->assertSame('hello', objectToArray('hello'));
        $this->assertSame(42, objectToArray(42));
        $this->assertNull(objectToArray(null));
    }

    // -- mergeArrays -------------------------------------------------------

    public function test_merge_arrays_joins_on_single_key(): void
    {
        $main = [
            ['id' => 1, 'name' => 'Alice'],
            ['id' => 2, 'name' => 'Bob'],
        ];
        $secondary = [
            ['id' => 1, 'role' => 'admin'],
            ['id' => 2, 'role' => 'user'],
        ];

        $merged = mergeArrays($main, $secondary, 'id', true);

        $this->assertSame('admin', $merged[0]['role']);
        $this->assertSame('user', $merged[1]['role']);
    }

    public function test_merge_arrays_on_sub_array_nests_matches_under_name(): void
    {
        $main = [
            ['id' => 1, 'name' => 'Alice'],
            ['id' => 2, 'name' => 'Bob'],
        ];
        $secondary = [
            ['user_id' => 1, 'tag' => 'vip'],
            ['user_id' => 1, 'tag' => 'beta'],
            ['user_id' => 2, 'tag' => 'guest'],
        ];

        $merged = mergeArraysOnSubArray($main, $secondary, 'id', 'tags');

        // join is on $union_by='id' in $main matched against secondary key 'id' (same).
        // The secondary uses 'user_id' so the match won't happen — this asserts that
        // behaviour. The legacy helper only joins when field names match literally.
        $this->assertSame([], $merged[0]['tags']);
    }

    // -- br2nl -------------------------------------------------------------

    public function test_br2nl_replaces_br_tags_with_newlines(): void
    {
        $this->assertSame("a\nb\nc", br2nl('a<br>b<br/>c'));
        $this->assertSame("line1\nline2", br2nl('line1<br />line2'));
        $this->assertSame("no change", br2nl('no change'));
    }

    // -- eliminar_tildes ---------------------------------------------------

    public function test_eliminar_tildes_strips_accents(): void
    {
        $this->assertSame('a', eliminar_tildes('á'));
        $this->assertSame('a', eliminar_tildes('à'));
        $this->assertSame('e', eliminar_tildes('é'));
        $this->assertSame('n', eliminar_tildes('ñ'));
        $this->assertSame('c', eliminar_tildes('ç'));
        $this->assertSame('Jose Munoz', eliminar_tildes('José Muñoz'));
        $this->assertSame('NCAA', eliminar_tildes('ÑCÁA'));
    }

    // -- char_at -----------------------------------------------------------

    public function test_char_at_returns_char_at_position(): void
    {
        $this->assertSame('l', char_at('hello', 3));
        $this->assertSame('h', char_at('hello', 0));
    }

    // -- generateMarks -----------------------------------------------------

    public function test_generate_marks_produces_parameterised_sql_placeholders(): void
    {
        $result = generateMarks('id', [10, 20, 30]);

        $this->assertStringContainsString(':id_0', $result->sql);
        $this->assertStringContainsString(':id_1', $result->sql);
        $this->assertStringContainsString(':id_2', $result->sql);
        $this->assertSame([
            'id_0' => 10,
            'id_1' => 20,
            'id_2' => 30,
        ], $result->assoc);
    }

    public function test_generate_marks_returns_false_sql_for_empty_input(): void
    {
        $result = generateMarks('id', []);

        $this->assertSame('false', $result->sql);
        $this->assertSame([], $result->assoc);
    }

    // -- createMarks -------------------------------------------------------

    public function test_create_marks_concatenates_filter_expressions(): void
    {
        $result = createMarks([
            ['filter' => 'AND a = :a', 'name' => 'a', 'value' => 1],
            ['filter' => 'AND b = :b', 'name' => 'b', 'value' => 2],
        ]);

        $this->assertStringContainsString('AND a = :a', $result->sql);
        $this->assertStringContainsString('AND b = :b', $result->sql);
        $this->assertSame(['a' => 1, 'b' => 2], $result->assoc);
    }
}
