import {Controller} from 'stimulus';
import Swal from 'sweetalert2';

export default class extends Controller 
{
    onSubmit(event)
    {
        event.preventDefault();
        this.element.submit();

    }

    
}

// event.preventDefault();
// Swal.fire(
// 'Good job!',
// 'You clicked the button!',
// 'success'
// ).then((result) => {
//     if(result.isConfirmed) {
//         this.element.submit();
//     }
// })