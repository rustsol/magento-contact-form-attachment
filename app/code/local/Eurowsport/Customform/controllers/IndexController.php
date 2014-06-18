<?php
require_once 'Mage/Contacts/controllers/IndexController.php';
class Eurowsport_Customform_IndexController extends Mage_Contacts_IndexController
{
    const XML_PATH_EMAIL_RECIPIENT  = 'contacts/email/recipient_email';
    const XML_PATH_EMAIL_SENDER     = 'contacts/email/sender_email_identity';
    const XML_PATH_EMAIL_TEMPLATE   = 'contacts/email/email_template';
    const XML_PATH_ENABLED          = 'contacts/contacts/enabled';

    public function preDispatch()
    {
        parent::preDispatch();

        if( !Mage::getStoreConfigFlag(self::XML_PATH_ENABLED) ) {
            $this->norouteAction();
        }
    }

    public function indexAction()
    {   
        $this->loadLayout();
        $this->getLayout()->getBlock('customform')
            ->setFormAction( Mage::getUrl('*/*/post') );
         
        $this->_initLayoutMessages('customer/session');
        $this->_initLayoutMessages('catalog/session');
        $this->renderLayout();
    }

    public function postAction()
    {
        $post = $this->getRequest()->getPost();        
        
        if ($post) {
            
            $translate = Mage::getSingleton('core/translate');
            
            
            /* @var $translate Mage_Core_Model_Translate */
            $translate->setTranslateInline(false);
            
            try {
                $postObject = new Varien_Object();
                $postObject->setData($post);
                
                $error = false;
                  
                $fileName = '';
                if (isset($_FILES['attachment']['name']) && $_FILES['attachment']['name'] != '') {
                    try {
                        $fileName       = $_FILES['attachment']['name'];
                        $fileExt        = strtolower(substr(strrchr($fileName, ".") ,1));
                        $fileNamewoe    = rtrim($fileName, $fileExt);
                        $fileName       = preg_replace('/\s+', '', $fileNamewoe) . time() . '.' . $fileExt;
 
                        $uploader       = new Varien_File_Uploader('attachment');
                        $uploader->setAllowedExtensions(array('jpg','jpeg','gif','png','bmp','svg'));
                        $uploader->setAllowRenameFiles(false);
                        $uploader->setFilesDispersion(false);
                        $path = Mage::getBaseDir('media') . DS . 'Customform';
                        if(!is_dir($path)){
                            mkdir($path, 0777, true);
                        }
                        $uploader->save($path . DS, $fileName );
 
                    } catch (Exception $e) {
                        $error = true;
                    }
                }
                
                if ($error) {
                    Mage::throwException('Please upload an image file only with type of GIF, PNG, JPG, JPEG SVG and BMP');
                }       
                
                $mailTemplate = Mage::getModel('core/email_template');
                /* @var $mailTemplate Mage_Core_Model_Email_Template */
                
                $attachmentFilePath = Mage::getBaseDir('media'). DS . 'Customform' . DS . $fileName;
                if(file_exists($attachmentFilePath)){
                    $fileContents = file_get_contents($attachmentFilePath);
                    $attachment   = $mailTemplate->getMail()->createAttachment($fileContents);
                    $attachment->filename = $fileName;
                }
                   
                $mailTemplate->setDesignConfig(array('area' => 'frontend'))
                    ->setReplyTo($post['email'])
                    ->sendTransactional(
                        Mage::getStoreConfig(self::XML_PATH_EMAIL_TEMPLATE),
                        Mage::getStoreConfig(self::XML_PATH_EMAIL_SENDER),
                        Mage::getStoreConfig(self::XML_PATH_EMAIL_RECIPIENT),
                        null,
                        array('data' => $postObject)
                    );

                if (!$mailTemplate->getSentSuccess()) {
                    throw new Exception();
                }

                $translate->setTranslateInline(true);

                Mage::getSingleton('customer/session')->addSuccess(Mage::helper('contacts')->__('Your inquiry was submitted and will be responded to as soon as possible. Thank you for contacting us.'));
                
                $this->_redirect('*/*/');

                return;
            } catch (Exception $e) {
                $translate->setTranslateInline(true);                
                $errorMsg = $e->getMessage() ? $e->getMessage() : 'Unable to submit your request. Please, try again later';
                Mage::getSingleton('customer/session')->addError(Mage::helper('contacts')->__($errorMsg));
                $this->_redirect('*/*/');
                return;
            }

        } else {
            $this->_redirect('*/*/');
        }
    }
}