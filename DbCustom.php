<?php

class DbCustom {

    public static function createTable( $table_name, $table_columns, $indexes = [] ){
        if( empty( $table_name ) || empty( $table_columns )) return false;
        global $wpdb;

        $create_table_query = static::prepareCreateTableQuery( $table_name, $table_columns );

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $create_table_query );

        static::setIndexes( $table_name, $indexes );

        return true;
    }

    public static function prepareCreateTableQuery( $table_name, $table_columns ){
        if( empty( $table_name ) || empty( $table_columns )) return '';
        global $wpdb;

        $query = "CREATE TABLE IF NOT EXISTS `$table_name` (";

        foreach( $table_columns as $single_key => $single_column ){
            $query .= "`$single_key` " . $single_column .', ';
        }

        $query = rtrim( $query, ", ");
        $query .= ") {$wpdb->get_charset_collate()};";;

        return $query;
    }

    public static function setIndexes( $table_name,  $indexes ){
        if( empty( $table_name ) || empty( $indexes )) return false;
        global $wpdb;

        if( !empty( $indexes ) ){
            foreach( $indexes as $index_name => $unique ){
                $unique = !empty($unique) ? "UNIQUE" : "";

                $wpdb->query( "CREATE $unique INDEX $index_name ON $table_name ($index_name)" );
            }
        }

        return true;
    }

    public static function insert( $table_name, $data ){
        global $wpdb;

        $format = static::prepareFormatArray( $data );
        $wpdb->insert( $table_name, $data, $format );

        return true;
    }

    public static function addCol( $table_name, $column_name, $column_type ){
        global $wpdb;

        $wpdb->query( "ALTER TABLE {$table_name} ADD {$column_name} {$column_type}" );

        return true;
    }

    public static function prepareFormatArray( $data ) {
        $format = [];

        foreach( $data as $data_single ){
            if( is_int( $data_single )){
                $format[] = '%d';
            } else if( is_float( $data_single ) ){
                $format[] = '%f';
            } else {
                $format[] = '%s';
            }
        }

        return $format;
    }

    public static function update( $table_name, $data, $where ){
        global $wpdb;

        $format         = static::prepareFormatArray( $data );
        $where_format   = static::prepareFormatArray( $where );

        return $wpdb->update( $table_name, $data, $where, $format, $where_format );
    }

    public static function getRow( $table_name, $where, $select_column = '*' ){
        global $wpdb;
        $sql = static::prepareGetQuery( $table_name, $where, $select_column );

        return $wpdb->get_row($sql, ARRAY_A);
    }

    public static function getResults( $table_name, $where, $select_column = '*',
    $limit = 0, $offset = 0, $sort_by = [], $custom_where = ''){
        global $wpdb;

        return $wpdb->get_results(
            static::prepareGetQuery( $table_name, $where,$select_column, $limit,
            $offset, $sort_by, $custom_where ), ARRAY_A
        );
    }

    public static function prepareGetQuery( $table_name, $where = [],
    $select_column = '*', $limit = 0, $offset = 0, $sort_by = [], $custom_where = ''){
        global $wpdb;

        $variables      = [];
        $query          = "SELECT $select_column FROM $table_name";
        $where_or_and   = ' WHERE';

        if( !empty( $where )){
            foreach( $where as $where_key => $where_single ){
                $variables[] = $where_single;
                $query      .= $where_or_and." $where_key";

                if( is_array( $where_single ) ){
                    $query .= " IN (".implode(', ', $where_single).")";
                    continue;
                } else if( is_int( $where_single ) ){
                    $query .= " = %d";
                } else if( is_float( $where_single ) ){
                    $query .= " LIKE %f";
                } else {
                    $query .= " LIKE %s";
                }

                $where_or_and = ' AND';
            }
        }

        if( !empty( $custom_where ) ) $query .= $where_or_and . ' ' . $custom_where;

        static::addSortToQuery( $query, $sort_by );
        static::addLimitAndOffsetToQuery( $query, $limit, $offset );

        return $wpdb->prepare( $query, $variables );
    }

    public static function addSortToQuery( &$query, $sort_by ){
        if( !empty( $sort_by ) ){
            $query .= ' ORDER BY';
            $sort_by_count = count( $sort_by );

            foreach( $sort_by as $sort_by_key => $sort_by_single ){
                $query .= " $sort_by_key $sort_by_single";
                $sort_by_count--;
                if( !empty( $sort_by_count ) ) $query .= ',';
            }
        }
    }

    public static function addLimitAndOffsetToQuery( &$query, $limit, $offset ){
        if( !empty( $limit ) ){
            $query .= ' LIMIT ' . $limit;

            if( !empty( $offset ) ){
                $query .= ' OFFSET ' . $offset;
            }
        }
    }
}