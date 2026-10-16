import './bootstrap';
import Alpine from 'alpinejs';
import setupForm from './components/setup-form';
import processingScreen from './components/processing-screen';
import correctionMode from './components/correction-mode';

Alpine.data('setupForm', setupForm);
Alpine.data('processingScreen', processingScreen);
Alpine.data('correctionMode', correctionMode);

window.Alpine = Alpine;
Alpine.start();
