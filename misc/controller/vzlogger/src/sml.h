/**
 * libsml header
 *
 * based on SML, Smart Message Language specification
 * Version 1.03 from 12. Nov 2008
 *
 * @package controller
 * @copyright Copyright (c) 2010, The volkszaehler.org project
 * @license http://www.gnu.org/licenses/gpl.txt GNU Public License
 * @author Steffen Vogel <info@steffenvogel.de>
 */
/*
 * This file is part of volkzaehler.org
 *
 * volkzaehler.org is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version.
 *
 * volkzaehler.org is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with volkszaehler.org. If not, see <http://www.gnu.org/licenses/>.
 */
 
#ifndef _SML_H_
#define _SML_H_

typedef uint8_t SML_Unit; /* DLMS-Unit-List, lookup in IEC 62056-62 */
typedef uint64_t SML_Status;
typedef char * SML_Signature;

struct SML_File {
	uint8_t version; /* should be 1 (v2 is not supported atm) */
	struct SML_Message * messages; /* linked list */
	uint16_t crc16;
}

struct SML_Message {
	char * transactionId;
	uint8_t groupNo;
	uint8_t abortOnError;
	uint16_t crc16;
	
	uint32_t tag;
	union { /* SML_MessageBody */
		struct SML_PublicOpen.Res * openResponse;	/* 0x00000101 */
		struct SML_PublicClose.Res * closeResponse;	/* 0x00000201 */
		struct SML_GetList.Res * getListResponse;	/* 0x00000701 */
	}
	
	SML_Message * next; /* NULL for end of list */
}

SML_Time {
	uint8_t tag;
	union { /* SML_Timestamp */
		uint32_t secIndex;	/* 0x01 */
		uint32_t timestamp;	/* 0x02 */
	}
}

struct SML_PublicOpen.Res {
	char * codepage; /* optional */
	char * clientId; /* optional */
	char * reqFileId;
	char * serverId;
	struct SML_Time refTime; /* optional */
	uint8_t smlVersion; /* optional */
}

struct SML_PublicClose.Res {
	SML_Signature globalSignature; /* optional */
}

struct SML_GetList.Res {
	char * clientId; /* optional */
	char * serverId;
	char * listName; /* optional */
	struct SML_Time actSensorTime; /* optional */
	struct SML_List valList;
	struct SML_Signature listSignature; /* optional */
	struct SML_Time actGatewayTime; /* optional */
}

struct SML_List {
	struct SML_ListEntry * valListEntry;
	struct SML_ListEntry * next; /* NULL for end of list */
}

struct SML_ListEntry {
	char * objName;
	SML_Status status; /* optional */
	struct SML_Time valTime; /* optional */
	SML_Unit unit; /* optional */
	uint8_t scaler; /* optional */
	struct SML_Value value;
	struct SML_Signature valueSignature; /* optional */
}

/* functions */
struct SML_File SML_Parse(char * data);
void SML_Free(struct SML_File * file);
uint16_t SML_Crc16(char * data, size_t length);

#endif /* _SML_H_ */
