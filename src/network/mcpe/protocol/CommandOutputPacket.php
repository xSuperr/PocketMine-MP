<?php

/*
 *
 *  ____            _        _   __  __ _                  __  __ ____
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_| |  | | | | | |  __/_____| |  | |  __/
 * |_|   \___/ \___|_|\_\___|\__|_|  |_|_|_| |_|\___|     |_|  |_|_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PocketMine Team
 * @link http://www.pocketmine.net/
 *
 *
*/

declare(strict_types=1);

namespace pocketmine\network\mcpe\protocol;

#include <rules/DataPacket.h>

use pocketmine\network\mcpe\protocol\serializer\NetworkBinaryStream;
use pocketmine\network\mcpe\protocol\types\command\CommandOriginData;
use pocketmine\network\mcpe\protocol\types\command\CommandOutputMessage;
use pocketmine\utils\BinaryDataException;
use function count;

class CommandOutputPacket extends DataPacket implements ClientboundPacket{
	public const NETWORK_ID = ProtocolInfo::COMMAND_OUTPUT_PACKET;

	/** @var CommandOriginData */
	public $originData;
	/** @var int */
	public $outputType;
	/** @var int */
	public $successCount;
	/** @var CommandOutputMessage[] */
	public $messages = [];
	/** @var string */
	public $unknownString;

	protected function decodePayload(NetworkBinaryStream $in) : void{
		$this->originData = $in->getCommandOriginData();
		$this->outputType = $in->getByte();
		$this->successCount = $in->getUnsignedVarInt();

		for($i = 0, $size = $in->getUnsignedVarInt(); $i < $size; ++$i){
			$this->messages[] = $this->getCommandMessage($in);
		}

		if($this->outputType === 4){
			$this->unknownString = $in->getString();
		}
	}

	/**
	 * @throws BinaryDataException
	 */
	protected function getCommandMessage(NetworkBinaryStream $in) : CommandOutputMessage{
		$message = new CommandOutputMessage();

		$message->isInternal = $in->getBool();
		$message->messageId = $in->getString();

		for($i = 0, $size = $in->getUnsignedVarInt(); $i < $size; ++$i){
			$message->parameters[] = $in->getString();
		}

		return $message;
	}

	protected function encodePayload(NetworkBinaryStream $out) : void{
		$out->putCommandOriginData($this->originData);
		$out->putByte($this->outputType);
		$out->putUnsignedVarInt($this->successCount);

		$out->putUnsignedVarInt(count($this->messages));
		foreach($this->messages as $message){
			$this->putCommandMessage($message, $out);
		}

		if($this->outputType === 4){
			$out->putString($this->unknownString);
		}
	}

	protected function putCommandMessage(CommandOutputMessage $message, NetworkBinaryStream $out) : void{
		$out->putBool($message->isInternal);
		$out->putString($message->messageId);

		$out->putUnsignedVarInt(count($message->parameters));
		foreach($message->parameters as $parameter){
			$out->putString($parameter);
		}
	}

	public function handle(PacketHandlerInterface $handler) : bool{
		return $handler->handleCommandOutput($this);
	}
}