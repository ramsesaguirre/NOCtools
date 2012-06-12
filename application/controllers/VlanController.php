<?php

/*
    Copyright (c) 2012, Open Source Solutions Limited, Dublin, Ireland
    All rights reserved.

    This file is part of the NOCtools package.

    Contact: Barry O'Donovan - barry (at) opensolutions (dot) ie
    http://www.opensolutions.ie/

    Redistribution and use in source and binary forms, with or without
    modification, are permitted provided that the following conditions are met:

    * Redistributions of source code must retain the above copyright
    notice, this list of conditions and the following disclaimer.
    * Redistributions in binary form must reproduce the above copyright
    notice, this list of conditions and the following disclaimer in the
    documentation and/or other materials provided with the distribution.
    * Neither the name of Open Source Solutions Limited nor the
    names of its contributors may be used to endorse or promote products
    derived from this software without specific prior written permission.

    THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
    ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
    WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
    DISCLAIMED. IN NO EVENT SHALL <COPYRIGHT HOLDER> BE LIABLE FOR ANY
    DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
    (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
    LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
    ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
    (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
    SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*/

/**
 * VlanController
 *
 * @author Barry O'Donovan <barry@opensolutions.ie>
 */

class VlanController extends OSS_Controller_Action
{

    /**
     * The default action - show the home page
     */
    public function indexAction()
    {
    }

    public function compareAction()
    {
        if( $this->getRequest()->isPost() )
        {
            // get details for source
            $devices_vlans = array();
            $source = $this->_getParam( 'source' );
            try
            {
                $host = new \OSS\SNMP( $source, $this->_options['community'] );
                $devices_vlans[ $source ] = $host->useCisco_VTP()->vlanNames();
                unset( $host );
            }
            catch( \OSS\Exception $e )
            {
                $this->addMessage( "Could not get VLANs from " . $source, OSS_Message::ERROR );
                return;
            }

            $odevices = $this->_getParam( 'odevices', false );

            if( is_array( $odevices ) && count( $odevices ) )
            {
                foreach( $odevices as $dev )
                {
                    try
                    {
                        $host = new \OSS\SNMP( $dev, $this->_options['community'] );
                        $devices_vlans[ $dev ] = $host->useCisco_VTP()->vlanNames();
                        unset( $host );
                    }
                    catch( \OSS\Exception $e )
                    {
                        $this->addMessage( "Could not get VLANs from " . $dev, OSS_Message::WARNING );
                        if( isset( $devices_vlans[ $dev ] ) ) unset( $devices_vlans[ $dev ] );
                    }
                }
            }

            $allVlans     = array();
            $nameMismatch = array();

            foreach( $devices_vlans as $device => $vlans )
            {
                foreach( $vlans as $vid => $vname )
                {
                    if( !isset( $allVlans[ $vid ] ) )
                        $allVlans[ $vid ] = $vname;
                    else
                    {
                        if( $device != $source && isset( $devices_vlans[ $source ][ $vid ] ) && $devices_vlans[ $source ][ $vid ] != $vname )
                        {
                            if( !isset( $nameMismatch[ $device ] ) )
                                $nameMismatch[ $device ] = array();

                            $nameMismatch[ $device ][$vid] = true;
                        }
                    }
                }
            }

            $this->view->allVlans      = $allVlans;
            $this->view->devices_vlans = $devices_vlans;
            $this->view->nameMismatch  = $nameMismatch;
        }

    }

}
