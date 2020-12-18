<?php


namespace Pledg\SyliusPaymentPlugin\Payum\Action;


use Payum\Core\Action\ActionInterface;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Reply\HttpRedirect;
use Pledg\SyliusPaymentPlugin\Payum\Request\RedirectUrlInterface;
use Pledg\SyliusPaymentPlugin\RedirectUrl\EncoderInterface;
use Pledg\SyliusPaymentPlugin\RedirectUrl\ParamBuilderFactoryInterface;

class RedirectUrlAction implements ActionInterface
{
    /** @var ParamBuilderFactoryInterface */
    protected $paramBuilderFactory;

    /** @var EncoderInterface */
    protected $encoder;

    public function __construct(ParamBuilderFactoryInterface $paramBuilderFactory, EncoderInterface $encoder)
    {
        $this->paramBuilderFactory = $paramBuilderFactory;
        $this->encoder = $encoder;
    }

    /**
     * @param RedirectUrlInterface|mixed $request
     */
    public function execute($request): void
    {
        RequestNotSupportedException::assertSupports($this, $request);


        $parameters = $this->paramBuilderFactory->fromRedirectUrlRequest($request)->build();
        $token = $this->encoder->encode($parameters, $request->getMerchant()->getSecret());

        //throw new HttpRedirect('https://staging.front.ecard.pledg.co/purchase?' . http_build_query($parameters));

        $this->setPaymentDetails($request, $parameters, $token);

        throw new HttpRedirect('https://staging.front.ecard.pledg.co/purchase?' . http_build_query($parameters));
    }

    private function setPaymentDetails(RedirectUrlInterface $request, array $parameters, string $token): void
    {
        $request->getPayment()->setDetails([
            'redirect_parameters' => $parameters,
            'redirect_urls' => [
                'plain_url' => 'https://staging.front.ecard.pledg.co/purchase?' . http_build_query($parameters),
                'signed_url' => 'https://staging.front.ecard.pledg.co/purchase?signature=' . $token
            ]
        ]);
    }

    public function encodeURIComponent(string $str): string
    {
        $revert = array('%21'=>'!', '%2A'=>'*', '%27'=>"'", '%28'=>'(', '%29'=>')');
        return strtr(rawurlencode($str), $revert);
    }

    public function supports($request)
    {
        return $request instanceof RedirectUrlInterface;
    }

}
