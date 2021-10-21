$(function(){
    const initIndex = async () => {
        function testtest() {
            alert('testtesttesttest');
        }

        $('.btn-primary').on('click', (event) => {
          event.preventDefault();
          testtest();
        });
        //testtest();
    };

    initIndex();
});
