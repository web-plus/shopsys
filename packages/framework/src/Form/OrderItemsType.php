<?php

namespace Shopsys\FrameworkBundle\Form;

use Shopsys\FrameworkBundle\Form\Admin\Order\OrderItemFormType;
use Shopsys\FrameworkBundle\Form\Admin\Order\OrderPaymentFormType;
use Shopsys\FrameworkBundle\Form\Admin\Order\OrderTransportFormType;
use Shopsys\FrameworkBundle\Model\Order\Item\OrderItemPriceCalculation;
use Shopsys\FrameworkBundle\Model\Order\Order;
use Shopsys\FrameworkBundle\Model\Payment\PaymentFacade;
use Shopsys\FrameworkBundle\Model\Transport\TransportFacade;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OrderItemsType extends AbstractType
{
    /**
     * @var \Shopsys\FrameworkBundle\Model\Transport\TransportFacade
     */
    private $transportFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Payment\PaymentFacade
     */
    private $paymentFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Order\Item\OrderItemPriceCalculation
     */
    private $orderItemPriceCalculation;

    /**
     * @param \Shopsys\FrameworkBundle\Model\Transport\TransportFacade $transportFacade
     * @param \Shopsys\FrameworkBundle\Model\Payment\PaymentFacade $paymentFacade
     * @param \Shopsys\FrameworkBundle\Model\Order\Item\OrderItemPriceCalculation $orderItemPriceCalculation
     */
    public function __construct(
        TransportFacade $transportFacade,
        PaymentFacade $paymentFacade,
        OrderItemPriceCalculation $orderItemPriceCalculation
    ) {
        $this->transportFacade = $transportFacade;
        $this->paymentFacade = $paymentFacade;
        $this->orderItemPriceCalculation = $orderItemPriceCalculation;
    }

    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $payments = $this->paymentFacade->getVisibleByDomainId($options['order']->getDomainId());
        if (!in_array($options['order']->getPayment(), $payments, true)) {
            $payments[] = $options['order']->getPayment();
        }

        $transports = $this->transportFacade->getVisibleByDomainId($options['order']->getDomainId(), $payments);
        if (!in_array($options['order']->getTransport(), $transports, true)) {
            $transports[] = $options['order']->getTransport();
        }

        $builder
            ->add('itemsWithoutTransportAndPayment', CollectionType::class, [
                'entry_type' => OrderItemFormType::class,
                'error_bubbling' => false,
                'allow_add' => true,
                'allow_delete' => true,
            ])
            ->add('orderPayment', OrderPaymentFormType::class, [
                'payments' => $payments,
            ])
            ->add('orderTransport', OrderTransportFormType::class, [
                'transports' => $transports,
            ]);
    }

    /**
     * @inheritdoc
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        parent::buildView($view, $form, $options);
        $view->vars['order'] = $options['order'];
        $view->vars['orderItemTotalPricesById'] = $this->orderItemPriceCalculation->calculateTotalPricesIndexedById($options['order']->getItems());
        $view->vars['transportPricesWithVatByTransportId'] = $this->transportFacade->getTransportPricesWithVatIndexedByTransportId(
            $options['order']->getCurrency()
        );
        $view->vars['transportVatPercentsByTransportId'] = $this->transportFacade->getTransportVatPercentsIndexedByTransportId();
        $view->vars['paymentPricesWithVatByPaymentId'] = $this->paymentFacade->getPaymentPricesWithVatIndexedByPaymentId(
            $options['order']->getCurrency()
        );
        $view->vars['paymentVatPercentsByPaymentId'] = $this->paymentFacade->getPaymentVatPercentsIndexedByPaymentId();
    }

    /**
     * @param \Symfony\Component\OptionsResolver\OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setRequired(['order'])
            ->addAllowedTypes('order', [Order::class, 'null'])
            ->setDefaults([
                'inherit_data' => true,
                'render_form_row' => false,
            ]);
    }

    /**
     * @return null|string
     */
    public function getParent()
    {
        return FormType::class;
    }
}
