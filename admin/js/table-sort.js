jQuery(document).ready(function($) {
    var compare = {
        name: function(a, b) {
            a = a.replace(/^@/, '') && a.replace(/-/g, '');
            b = b.replace(/^@/, '') && b.replace(/-/g, '');

            if (a < b) {
                return -1;
            } else {
                return a > b ? 1 : 0;
            }
        },
        lbryurl: function(a, b) {
            a = a.replace(/^lbry:\/\/@/i, '') && a.replace(/#[a-zA-Z0-9]+/, '') && a.replace(/-/g, '');
            b = b.replace(/^lbry:\/\/@/i, '') && b.replace(/#[a-zA-Z0-9]+/, '') && b.replace(/-/g, '');

            if (a < b) {
                return -1;
            } else {
                return a > b ? 1 : 0;
            }
        },
        amount: function(a, b) {
            a = a.split('.');
            b = b.split('.');

            a = Number(a[0]) + Number(a[1]);
            b = Number(b[0]) + Number(b[1]);

            return a - b;
        },
        number: function(a, b) {
            a = Number(a);
            b = Number(b);

            return a - b;
        },
        date: function(a, b) {
            a = new Date(a);
            b = new Date(b);

            return a - b;
        }
    };
    $('.lbry-channel-table').each(function() {
        var $table = $(this);
        var $tbody = $table.find('tbody');
        var $controls = $table.find('th');
        var rows = $tbody.find('tr').toArray();

        $controls.on('click', function() {
            var $header = $(this);
            var order = $header.data('sort');
            var column;

            if ($header.is('.ascending') || $header.is('.descending')) {
                $header.toggleClass('ascending descending');
                $tbody.append(rows.reverse());
            } else {
                $header.addClass('ascending');
                $header.siblings().removeClass('ascending descending');
                if (compare.hasOwnProperty(order)) {
                    column = $controls.index(this);

                    rows.sort(function(a, b) {
                        a = $(a).find('td').eq(column).text();
                        b = $(b).find('td').eq(column).text();
                        return compare[order](a, b);
                    });

                    $tbody.append(rows);
                }
            }
        });
    });
});