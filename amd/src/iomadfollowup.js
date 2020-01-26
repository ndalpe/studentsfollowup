define(
    [
        'jquery',
        '/report/iomadfollowup/amd/build/datatables.min.js',
        '/report/iomadfollowup/amd/build/jquery.floatThead.min.js'
    ],
    function($) {
    return {
        init: function() {
            $(document).ready(function() {

                // Initialize the DataTable and float its header
                floatTheadInit();

                function dataTableInit(){

                    // https://datatables.net/
                    // $( selector ).DataTable() and $( selector ).dataTable().
                    // The former returns a DataTables API instance, while the latter returns a jQuery object.
                    return $('#reportTbl').dataTable({
                        "bInfo": false,
                        "bLengthChange": false,
                        "iDisplayLength": 1000,
                        "paging": false,
                        "retrieve": true,
                        // "language": {
                        //     "search": "Search in table:"
                        // }
                    });
                }

                function floatTheadInit(){

                    // get DataTable instance
                    var reportTbl = dataTableInit();

                    // http://mkoryak.github.io/floatThead/datatables/
                    reportTbl.floatThead({
                        top: function(){
                            return $(".main-header").height();
                        },
                        scrollContainer: function($table){
                            var $wrapper = $table.closest('.dataTables_wrapper');
                            $wrapper.css({'overflow': 'auto', 'height': '500px'});
                            return $wrapper;
                        }
                    });
                }

                // Apply the cohort filter
                $("#countries").change(function(){
                    document.location.href = "/report/iomadfollowup/index.php?c="+$("#countries option:selected").val();
                });

                // highlight a student's row on click
                $('#reportTbl tr').on('click', function(){$(this).toggleClass('selected');});

            }); // end document.ready
        } // end: init
    };
});