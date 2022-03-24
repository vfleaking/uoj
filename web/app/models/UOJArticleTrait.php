<?php

trait UOJArticleTrait {
    static string $table_for_content;
    static string $key_for_content;
    static array $fields_for_content;
    static string $table_for_tags;
    static string $key_for_tags;

    public ?array $tags = null;
    public ?array $content = null;

    public function queryContent() {
        if ($this->content === null) {
            $this->content = DB::selectFirst([
                "select", DB::fields(static::$fields_for_content), "from", static::$table_for_content,
                "where", [static::$key_for_content => $this->info['id']]
            ]);
        }
        return $this->content;
    }
    public function queryTags() {
        if ($this->tags === null) {
            $res = DB::selectAll([
                "select tag from", static::$table_for_tags,
                "where", [static::$key_for_tags => $this->info['id']],
                "order by id"
            ]);
            $this->tags = [];
            foreach ($res as $row) {
                $this->tags[] = $row['tag'];
            }
        }
        return $this->tags;
    }
    public function updateTags(array $tags) {
		if ($tags !== $this->queryTags()) {
			DB::delete([
                "delete from", static::$table_for_tags,
                "where", [static::$key_for_tags => $this->info['id']],
            ]);
            if ($tags) {
                $tuples = [];
                foreach ($tags as $tag) {
                    $tuples[] = [$this->info['id'], $tag];
                }
                DB::insert([
                    "insert into", static::$table_for_tags,
                    DB::bracketed_fields([static::$key_for_tags, 'tag']),
                    "values", DB::tuples($tuples)
                ]);
            }
		}
    }
}