define(
    [
        'jquery',
        '/report/studentsfollowup/amd/build/datatables.min.js',
        '/report/studentsfollowup/amd/build/jquery.floatThead.min.js',
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
                        "retrieve": true
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
                $("#cohorts").change(function(){
                    var c = $("#cohorts option:selected").val();
                    var m = $("#modes option:selected").val();
                    document.location.href = "/report/studentsfollowup/index.php?c="+c+"&m="+m;
                });
                // $("#modes").change(function(){
                //     var c = $("#cohorts option:selected").val();
                //     var m = $("#modes option:selected").val();
                //     document.location.href = "/report/studentsfollowup/index.php?c="+c+"&m="+m;
                // });

                // highlight a student's row on click
                $('#reportTbl tr').on('click', function(){
                    // use style attr toggle the the module is self-contained
                    $(this).attr('style', function(index, attr){
                        return attr != 'background-color:#89c4e5;' ? 'background-color:#89c4e5;' : 'background-color:transparent;';
                    });
                });
            }); // end document.ready
        } // end: init
    };
});